<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;


class TextToSpeechService
{
    protected $ffmpegPath;
    protected $ffprobePath;
    protected $apiKeys;
    protected $ttsUrl;
    protected $currentKeyIndex = 0;
    protected $maxRetries = 3;
    protected $chunkTimeout = 120; // Increased to 2 minutes per chunk
    protected $maxChunkLength = 500; // Reduced chunk size for better reliability
    protected $voiceName;
    // protected $ffmpegPath = '/home/austatwe/bin/ffmpeg';
    // protected $ffprobePath = '/home/austatwe/bin/ffprobe';

    public function __construct()
    {
        $rawKeys = config('services.gemini.api_key');
        $this->apiKeys = array_filter(array_map('trim', explode(',', $rawKeys)));
        if (empty($this->apiKeys)) {
            throw new \Exception("❌ Tidak ada GEMINI_API_KEY terdeteksi di .env");
        }

        $this->ttsUrl = config('services.gemini.tts_url');        // ✅ gunakan dari config
        $this->ffmpegPath  = config('services.ffmpeg.bin');                // ✅ gunakan dari config
        if (empty($this->ffmpegPath)) {
            // Add a check to fail early
            throw new \Exception("FFMPEG_PATH is not set in your environment.");
        }
        $this->ffprobePath = config('services.ffmpeg.probe');              // ✅ gunakan dari config
        $this->voiceName   = config('services.gemini.voice', 'Kore'); // ✅ Kore (case-sensitive)

        $this->currentKeyIndex = Cache::get('tts_current_key_index', 0);

        Log::info("🎙️ [TTS] Service initialized", [
            'available_keys' => count($this->apiKeys),
            'current_key_index' => $this->currentKeyIndex,
            'max_chunk_length' => $this->maxChunkLength,
            'chunk_timeout' => $this->chunkTimeout,
            'tts_url' => $this->ttsUrl,
            'ffmpeg' => $this->ffmpegPath,
            'ffprobe' => $this->ffprobePath,
            'voice' => $this->voiceName,
        ]);
    }

    private function getCurrentApiKey(): string
    {
        return $this->apiKeys[$this->currentKeyIndex];
    }

    private function switchApiKey(): void
    {
        $old = $this->currentKeyIndex;
        $this->currentKeyIndex = ($this->currentKeyIndex + 1) % count($this->apiKeys);

        // Save current key index to cache
        Cache::put('tts_current_key_index', $this->currentKeyIndex, 3600);

        Log::warning("🔄 [TTS] Switching API Key: {$old} → {$this->currentKeyIndex}");

        // Mark the old key as potentially problematic for 10 minutes
        Cache::put("api_key_last_error_{$old}", now(), 600);
    }

    private function isKeyRecentlyFailed(int $keyIndex): bool
    {
        return Cache::has("api_key_last_error_{$keyIndex}");
    }

    public function convertToAudio(string $text): array
    {
        return $this->convertToAudioWithProgress($text);
    }

    public function convertToAudioWithProgress(string $text, $progressCallback = null): array
    {
        $startTime = microtime(true);

        try {
            $cleanText = $this->sanitizeAndOptimizeText($text);

            if (empty($cleanText)) {
                throw new \Exception("Teks kosong setelah sanitasi");
            }

            Log::info("🎙️ [TTS] Starting text-to-audio conversion", [
                'text_length' => strlen($cleanText),
                'word_count'  => str_word_count($cleanText),
                'keys_count'  => count($this->apiKeys),
                'current_key_index' => $this->currentKeyIndex
            ]);

            $chunks = $this->splitTextForTTS($cleanText);
            $totalChunks = count($chunks);

            Log::info("📄 [TTS] Text split into {$totalChunks} chunks");

            // Validate chunks aren't too large
            foreach ($chunks as $i => $chunk) {
                if (strlen($chunk) > $this->maxChunkLength * 2) {
                    Log::warning("⚠️ [TTS] Chunk {$i} is very large: " . strlen($chunk) . " characters");
                }
            }

            $audioSegments = [];
            $failedChunks = 0;
            $maxFailedChunks = min(5, ceil($totalChunks * 0.3)); // Max 5 failures or 30%
            $consecutiveFailures = 0;
            $maxConsecutiveFailures = 3;

            foreach ($chunks as $i => $chunk) {
                // Progress callback at start of chunk
                if ($progressCallback) {
                    $progressCallback($i, $totalChunks, 'processing');
                }

                try {
                    Log::info("➡️ [TTS] Processing chunk " . ($i + 1) . "/{$totalChunks}", [
                        'chunk_length' => strlen($chunk),
                        'failed_chunks' => $failedChunks,
                        'consecutive_failures' => $consecutiveFailures
                    ]);

                    $audioFile = $this->generateAudioChunkWithRetry($chunk, $i);

                    if ($audioFile && file_exists($audioFile)) {
                        $audioSegments[] = $audioFile;
                        $consecutiveFailures = 0; // Reset on success

                        Log::info("✅ [TTS] Chunk {$i} completed successfully");

                        if ($progressCallback) {
                            $progressCallback($i, $totalChunks, 'completed');
                        }
                    } else {
                        throw new \Exception("Audio file not created or corrupted");
                    }
                } catch (\Exception $e) {
                    $failedChunks++;
                    $consecutiveFailures++;

                    Log::error("❌ [TTS] Chunk {$i} failed", [
                        'error' => $e->getMessage(),
                        'failed_chunks' => $failedChunks,
                        'consecutive_failures' => $consecutiveFailures,
                        'chunk_preview' => substr($chunk, 0, 100) . '...'
                    ]);

                    if ($progressCallback) {
                        $progressCallback($i, $totalChunks, 'failed');
                    }

                    // Abort if too many consecutive failures
                    if ($consecutiveFailures >= $maxConsecutiveFailures) {
                        throw new \Exception("Terlalu banyak kegagalan berturut-turut ({$consecutiveFailures}). Kemungkinan masalah serius dengan API atau jaringan.");
                    }

                    // Abort if too many total failures
                    if ($failedChunks >= $maxFailedChunks) {
                        throw new \Exception("Terlalu banyak chunk gagal ({$failedChunks}/{$totalChunks}). Proses dihentikan.");
                    }

                    // Skip failed chunk and continue
                    continue;
                }

                // Adaptive delay between chunks based on recent performance
                $delay = $this->calculateAdaptiveDelay($consecutiveFailures, $failedChunks);
                if ($delay > 0) {
                    Log::debug("⏱️ [TTS] Adaptive delay: {$delay}ms");
                    usleep($delay * 1000);
                }
            }

            if (empty($audioSegments)) {
                throw new \Exception("Semua chunk TTS gagal diproses. Periksa koneksi internet dan quota API.");
            }

            $successRate = (count($audioSegments) / $totalChunks) * 100;
            Log::info("🔗 [TTS] Combining " . count($audioSegments) . " audio segments", [
                'success_rate' => round($successRate, 2) . '%',
                'failed_chunks' => $failedChunks
            ]);

            $combinedMp3 = $this->combineAudioSegments($audioSegments);
            $duration = $this->getAudioDuration($combinedMp3);

            // Convert to FLAC with error handling
            $flacPath = null;
            try {
                // Assuming saveAsFlac now returns the PATH to the FLAC file, not its content
                $flacPath = $this->saveAsFlac($combinedMp3);
            } catch (\Exception $e) {
                Log::warning("⚠️ [TTS] FLAC conversion failed, continuing with MP3 only", [
                    'error' => $e->getMessage()
                ]);
            }

            // Read MP3 content

            // Clean up temp files
            foreach ($audioSegments as $segment) {
                @unlink($segment);
            }
            $mp3Size = file_exists($combinedMp3) ? filesize($combinedMp3) : 0;
            $flacSize = $flacPath && file_exists($flacPath) ? filesize($flacPath) : 0;
            $processingTime = round(microtime(true) - $startTime, 2);

            Log::info("✅ [TTS] Audio conversion completed successfully", [
                'processing_time_seconds' => $processingTime,
                'duration_seconds' => $duration,
                'mp3_size' => $mp3Size,
                'flac_size' => $flacSize,
                'successful_chunks' => count($audioSegments),
                'failed_chunks' => $failedChunks,
                'success_rate' => round($successRate, 2) . '%'
            ]);

            return [
                'mp3'      => $combinedMp3,
                'flac'     => $flacPath,
                'duration' => $duration,
            ];
        } catch (\Exception $e) {
            Log::error("❌ [TTS] Conversion failed completely", [
                'error' => $e->getMessage(),
                'processing_time' => round(microtime(true) - $startTime, 2),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function calculateAdaptiveDelay(int $consecutiveFailures, int $totalFailures): int
    {
        // Base delay
        $baseDelay = 200; // 200ms

        // Increase delay based on consecutive failures
        $consecutiveMultiplier = min($consecutiveFailures * 500, 2000); // Max 2 seconds

        // Increase delay if total failures are high
        $totalMultiplier = min($totalFailures * 100, 1000); // Max 1 second

        return $baseDelay + $consecutiveMultiplier + $totalMultiplier;
    }

    private function generateAudioChunkWithRetry(string $text, int $index): string
    {
        $attempts = 0;
        $maxAttempts = min(count($this->apiKeys) * $this->maxRetries, 15); // Max 15 attempts
        $lastError = null;
        $startTime = microtime(true);

        while ($attempts < $maxAttempts) {
            $apiKey = $this->getCurrentApiKey();

            // Skip recently failed keys if we have alternatives
            if ($this->isKeyRecentlyFailed($this->currentKeyIndex) && count($this->apiKeys) > 1) {
                Log::debug("⏭️ [TTS] Skipping recently failed key {$this->currentKeyIndex}");
                $this->switchApiKey();
                continue;
            }

            $url = $this->ttsUrl . '?key=' . $apiKey;

            $payload = [
                "contents" => [
                    [
                        "role" => "user",
                        "parts" => [["text" => $text]]
                    ]
                ],
                "generationConfig" => [
                    "responseModalities" => ["AUDIO"],
                    "speechConfig" => [
                        "voiceConfig" => [
                            "prebuiltVoiceConfig" => ["voiceName" => $this->voiceName]
                        ]
                    ]
                ]
            ];

            try {
                Log::debug("🌐 [TTS] API Request", [
                    'chunk' => $index,
                    'attempt' => $attempts + 1,
                    'key_index' => $this->currentKeyIndex,
                    'text_length' => strlen($text),
                    'url' => parse_url($url, PHP_URL_HOST)
                ]);

                $response = Http::withOptions([
                    'timeout' => $this->chunkTimeout,
                    'connect_timeout' => 30,
                    'read_timeout' => $this->chunkTimeout,
                    'verify' => false // Disable SSL verification if needed
                ])
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'User-Agent' => 'Laravel-TTS-Service/1.0'
                    ])
                    ->retry(2, 1000) // Built-in retry with 1 second delay
                    ->post($url, $payload);

                if ($response->successful()) {
                    $data = $response->json();

                    if (!isset($data['candidates'][0]['content']['parts'][0])) {
                        $lastError = "Invalid response structure";
                        Log::error("❌ [TTS] Invalid response structure", [
                            'chunk' => $index,
                            'response_keys' => array_keys($data)
                        ]);
                        throw new \Exception($lastError);
                    }

                    $part = $data['candidates'][0]['content']['parts'][0];
                    $mimeType = $part['inlineData']['mimeType'] ?? null;
                    $audioContent = $part['inlineData']['data'] ?? null;

                    if (!empty($audioContent)) {
                        $processingTime = round(microtime(true) - $startTime, 2);

                        Log::info("✅ [TTS] Chunk {$index} successful", [
                            'attempt' => $attempts + 1,
                            'key_index' => $this->currentKeyIndex,
                            'mime_type' => $mimeType,
                            'processing_time' => $processingTime,
                            'audio_size' => strlen($audioContent)
                        ]);

                        // Track successful usage
                        $this->trackApiUsage($this->currentKeyIndex);

                        return $this->saveAudioFile($mimeType, $audioContent, $index);
                    }

                    $lastError = "Empty audioContent in response";
                    Log::error("❌ [TTS] Empty audioContent", ['chunk' => $index]);
                } else {
                    $lastError = "HTTP {$response->status()}: " . $response->body();

                    if ($response->status() == 429) {
                        Log::warning("⚠️ [TTS] Rate limit hit, switching API key", [
                            'key_index' => $this->currentKeyIndex,
                            'chunk' => $index
                        ]);
                        $this->switchApiKey();
                        $attempts++;
                        sleep(min($attempts, 5)); // Progressive backoff
                        continue;
                    } else if ($response->status() >= 500) {
                        Log::warning("⚠️ [TTS] Server error, will retry", [
                            'status' => $response->status(),
                            'chunk' => $index
                        ]);
                        sleep(5); // Wait longer for server errors
                    } else if ($response->status() == 400) {
                        // Bad request - probably text issue, don't retry
                        throw new \Exception("Bad request - possibly invalid text: " . substr($text, 0, 100));
                    }

                    Log::warning("⚠️ [TTS] HTTP Error", [
                        'chunk' => $index,
                        'status' => $response->status(),
                        'body' => substr($response->body(), 0, 500),
                        'attempt' => $attempts + 1
                    ]);
                }
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $lastError = "Connection timeout: " . $e->getMessage();
                Log::warning("🌐 [TTS] Connection error", [
                    'error' => $e->getMessage(),
                    'chunk' => $index,
                    'attempt' => $attempts + 1
                ]);

                // For timeout errors, wait longer before retry
                sleep(min($attempts + 2, 10));
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::warning("⚠️ [TTS] Unexpected error", [
                    'error' => $e->getMessage(),
                    'chunk' => $index,
                    'attempt' => $attempts + 1
                ]);

                // For other errors, wait progressively longer
                sleep(min(pow(2, $attempts), 8));
            }

            // Switch API key after certain number of attempts with same key
            if (($attempts + 1) % $this->maxRetries == 0) {
                $this->switchApiKey();
            }

            $attempts++;
        }

        throw new \Exception("❌ Chunk {$index} failed after {$maxAttempts} attempts. Last error: {$lastError}");
    }

    private function saveAudioFile(?string $mimeType, string $audioContent, int $index): string
    {
        $rawData = base64_decode($audioContent);
        if ($rawData === false) {
            throw new \Exception("Failed to decode base64 audio content");
        }

        $tempDir = storage_path('app/temp_audio');
        if (!is_dir($tempDir) && !mkdir($tempDir, 0755, true)) {
            throw new \Exception("Failed to create temp audio directory");
        }

        $base = $tempDir . "/tts_chunk_{$index}_" . time();
        $wavPath = $base . ".wav";

        Log::debug("💾 [TTS] Saving audio file", [
            'chunk' => $index,
            'mime_type' => $mimeType,
            'raw_data_size' => strlen($rawData),
            'output_path' => $wavPath,
            'ffmpeg_exists' => $this->binaryExists($this->ffmpegPath),
        ]);

        try {
            // 1) Raw PCM
            if ($mimeType && str_contains($mimeType, 'audio/L16')) {
                $ffmpegOk = $this->binaryExists($this->ffmpegPath);

                if ($ffmpegOk) {
                    $rawPath = $base . ".raw";
                    file_put_contents($rawPath, $rawData);

                    // s16le 24kHz mono -> WAV 44.1kHz stereo (atau tetap mono 24kHz, sesuaikan kebutuhanmu)
                    $cmd = sprintf(
                        '"%s" -f s16le -ar 24000 -ac 1 -i "%s" -ar 44100 -ac 2 -y "%s" 2>&1',
                        $this->ffmpegPath,
                        $rawPath,
                        $wavPath
                    );
                    exec($cmd, $output, $code);
                    @unlink($rawPath);

                    if ($code !== 0 || !file_exists($wavPath)) {
                        Log::warning("⚠️ [TTS] FFmpeg failed, fallback to pure PHP WAV writer", [
                            'code' => $code,
                            'stderr' => implode("\n", $output ?? []),
                        ]);
                        $this->writeWavFromPcm($rawData, $wavPath, 24000, 1, 16);
                    }
                } else {
                    // FFmpeg tidak ada → langsung tulis header WAV
                    $this->writeWavFromPcm($rawData, $wavPath, 24000, 1, 16);
                }

                // 2) MP3 langsung → konversi ke WAV (opsional)
            } elseif (in_array($mimeType, ['audio/mp3', 'audio/mpeg'])) {
                $mp3Path = $base . ".mp3";
                file_put_contents($mp3Path, $rawData);

                if ($this->binaryExists($this->ffmpegPath)) {
                    $cmd = sprintf(
                        '"%s" -i "%s" -ar 44100 -ac 2 -y "%s" 2>&1',
                        $this->ffmpegPath,
                        $mp3Path,
                        $wavPath
                    );
                    exec($cmd, $output, $code);
                    @unlink($mp3Path);

                    if ($code !== 0 || !file_exists($wavPath)) {
                        throw new \Exception("FFmpeg MP3 conversion failed: " . implode("\n", $output ?? []));
                    }
                } else {
                    // Kalau tak ada ffmpeg dan kamu tidak butuh WAV, kamu boleh return MP3 path saja:
                    return $mp3Path;
                }

                // 3) Format tak dikenal → simpan apa adanya (hanya jika memang sudah WAV valid)
            } else {
                file_put_contents($wavPath, $rawData);
                // Kalau ternyata bukan WAV valid, pindah ke fallback:
                if (filesize($wavPath) < 44) {
                    // minimal header WAV 44 byte — fallback hapus & bikin WAV dari PCM asumsi s16le 24k mono
                    @unlink($wavPath);
                    $this->writeWavFromPcm($rawData, $wavPath, 24000, 1, 16);
                }
            }

            if (!file_exists($wavPath) || filesize($wavPath) === 0) {
                throw new \Exception("Output WAV file is empty or doesn't exist");
            }

            Log::debug("✅ [TTS] Audio file saved", [
                'chunk' => $index,
                'file_path' => $wavPath,
                'file_size' => filesize($wavPath)
            ]);

            return $wavPath;
        } catch (\Exception $e) {
            Log::error("❌ [TTS] Failed to save audio file", [
                'chunk' => $index,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function trackApiUsage(int $keyIndex): void
    {
        $usageKey = "api_key_usage_{$keyIndex}_" . date('Y-m-d-H');
        $currentUsage = Cache::get($usageKey, 0);
        Cache::put($usageKey, $currentUsage + 1, 3600);
    }

    private function sanitizeAndOptimizeText(string $text): string
    {
        // Remove common problematic characters and patterns
        $text = preg_replace('/[^\p{L}\p{N}\p{P}\p{Z}]/u', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/([.!?])\s*([.!?])/', '$1 ', $text);

        // Remove very long repeated characters
        $text = preg_replace('/(.)\1{10,}/', '$1$1$1', $text);

        return trim($text);
    }

    private function splitTextForTTS(string $text): array
    {
        // Split by sentences first
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $chunks = [];
        $current = '';

        foreach ($sentences as $sentence) {
            $proposedChunk = $current . $sentence . ' ';

            if (strlen($proposedChunk) <= $this->maxChunkLength) {
                $current = $proposedChunk;
            } else {
                if (!empty($current)) {
                    $chunks[] = trim($current);
                }

                // If single sentence is too long, split by clauses
                if (strlen($sentence) > $this->maxChunkLength) {
                    $clauses = preg_split('/[,;:]/', $sentence);
                    $clauseChunk = '';

                    foreach ($clauses as $clause) {
                        if (strlen($clauseChunk . $clause) <= $this->maxChunkLength) {
                            $clauseChunk .= $clause . ', ';
                        } else {
                            if (!empty($clauseChunk)) {
                                $chunks[] = trim($clauseChunk);
                            }
                            $clauseChunk = $clause . ', ';
                        }
                    }

                    if (!empty($clauseChunk)) {
                        $current = $clauseChunk;
                    } else {
                        $current = '';
                    }
                } else {
                    $current = $sentence . ' ';
                }
            }
        }

        if (!empty($current)) {
            $chunks[] = trim($current);
        }

        return array_filter($chunks, function ($chunk) {
            return strlen(trim($chunk)) > 0;
        });
    }

    private function combineAudioSegments(array $audioFiles): string
    {
        if (empty($audioFiles)) {
            throw new \Exception("No audio files to combine");
        }

        if (count($audioFiles) == 1) {
            // Single file - convert to MP3
            $mp3Path = str_replace('.wav', '.mp3', $audioFiles[0]);

            $cmd = "\"{$this->ffmpegPath}\" -i \"{$audioFiles[0]}\" -codec:a libmp3lame -b:a 128k \"{$mp3Path}\" 2>&1";
            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception("Failed to convert single WAV to MP3: " . implode("\n", $output));
            }

            return $mp3Path;
        }

        // Multiple files - create file list for ffmpeg
        $listFile = storage_path('app/temp_audio/file_list.txt');
        $fileListContent = '';

        foreach ($audioFiles as $file) {
            if (file_exists($file)) {
                $fileListContent .= "file '" . $file . "'\n";
            }
        }

        if (file_put_contents($listFile, $fileListContent) === false) {
            throw new \Exception("Failed to create file list for ffmpeg");
        }

        $outputPath = storage_path('app/temp_audio/combined_' . time() . '.mp3');
        $cmd = "\"{$this->ffmpegPath}\" -f concat -safe 0 -i \"{$listFile}\" -codec:a libmp3lame -b:a 128k \"{$outputPath}\" 2>&1";
        exec($cmd, $output, $returnCode);
        @unlink($listFile);

        if ($returnCode !== 0 || !file_exists($outputPath)) {
            throw new \Exception("Failed to combine audio files: " . implode("\n", $output));
        }

        return $outputPath;
    }

    private function getAudioDuration(string $filePath): float
    {
        if (!$this->binaryExists($this->ffprobePath)) {
            Log::warning("⚠️ [TTS] ffprobe not found, duration=0");
            return 0.0;
        }
        $cmd = sprintf(
            '"%s" -v quiet -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "%s" 2>&1',
            $this->ffprobePath,
            $filePath
        );
        $out = shell_exec($cmd);
        return $out ? (float) trim($out) : 0.0;
    }


    private function saveAsFlac(string $mp3Path): ?string
    {
        try {
            $flacPath = str_replace('.mp3', '.flac', $mp3Path);
            $cmd = "{$this->ffmpegPath} -i \"{$mp3Path}\" -codec:a flac \"{$flacPath}\" 2>&1";

            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && file_exists($flacPath)) {
                $flacData = file_get_contents($flacPath);
                @unlink($flacPath);
                return $flacData;
            }

            return null;
        } catch (\Exception $e) {
            Log::warning("⚠️ [TTS] FLAC conversion failed: " . $e->getMessage());
            return null;
        }
    }
    private function binaryExists(?string $bin): bool
    {
        if (!$bin) return false;
        // Jika absolute path file
        if (preg_match('/[\\\\\\/]/', $bin)) {
            return file_exists($bin);
        }
        // Jika hanya nama biner (mengandalkan PATH)
        $which = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where' : 'which';
        $out = [];
        @exec($which . ' ' . escapeshellarg($bin), $out, $code);
        return $code === 0 && !empty($out);
    }
    private function writeWavFromPcm(string $rawPcm, string $wavPath, int $sampleRate = 24000, int $channels = 1, int $bits = 16): void
    {
        $byteRate = $sampleRate * $channels * ($bits / 8);
        $blockAlign = $channels * ($bits / 8);
        $dataSize = strlen($rawPcm);
        $riffSize = 36 + $dataSize;

        $header = "RIFF" . pack('V', $riffSize) . "WAVE";
        $header .= "fmt " . pack('V', 16) . pack('v', 1);
        $header .= pack('v', $channels) . pack('V', $sampleRate);
        $header .= pack('V', $byteRate) . pack('v', $blockAlign) . pack('v', $bits);
        $header .= "data" . pack('V', $dataSize);

        @mkdir(dirname($wavPath), 0755, true);
        file_put_contents($wavPath, $header . $rawPcm);
    }
}
