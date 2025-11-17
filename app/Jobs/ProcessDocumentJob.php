<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\TextExtractionService;
use App\Services\TextToSpeechService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use App\Support\LogsDocumentConversion;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LogsDocumentConversion;

    protected $documentId; // Change from $document to $documentId
    public $timeout = 3600;
    public $tries = 3;
    public $maxExceptions = 5;
    public $backoff = [60, 300, 900];

    public function __construct(Document $document)
    {
        // Store only the ID instead of the full model
        $this->documentId = $document->id;

        // Set job tags for better monitoring
        $this->tags = ['document-processing', "document-{$document->id}"];
    }

    public function handle(TextExtractionService $textExtraction, TextToSpeechService $ttsService)
    {
        $startTime = microtime(true);

        try {
            // Try to load the document by ID
            $document = Document::find($this->documentId);

            if (!$document) {
                $this->logConversion(
                    status: 'error',
                    stage: 'document_not_found',
                    message: 'Dokumen tidak ditemukan di database, job dihentikan.',
                    meta: [
                        'document_id' => $this->documentId,
                    ],
                );

                // Clean up any progress cache
                $progressKey = "document_progress_{$this->documentId}";
                Cache::forget($progressKey);
                Cache::forget($progressKey . '_completed');

                // Delete the job silently - no need to retry
                $this->delete();
                return;
            }

            // Continue with normal processing

            $this->processDocument($document, $textExtraction, $ttsService, $startTime);
        } catch (ModelNotFoundException $e) {
            // Handle the specific case where model is not found
            $this->logConversion(
                status: 'error',
                stage: 'model_not_found',
                message: 'Model tidak ditemukan , job dihentikan.',
                meta: [
                    'document_id' => $this->documentId,
                ],
            );

            // Clean up and delete job
            $this->cleanupAndDelete();
            return;
        } catch (\Exception $e) {
            Log::error("❌ [Queue] Unexpected error processing document {$this->documentId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Let Laravel handle retries for other errors
            throw $e;
        }
    }

    private function processDocument(Document $document, TextExtractionService $textExtraction, TextToSpeechService $ttsService, float $startTime)
    {
        $documentId = $document->id;
        $progressKey = "document_progress_{$documentId}";

        try {
            // Validate document state
            $this->validateDocumentState($document);

            // $this->updateProgress($progressKey, 0, 'Memulai proses...', 'initializing');
            $this->updateProgress(
                progressKey: $progressKey,
                percent: 0,
                message: "Memulai proses...",
                stage: "initializing"
            );


            // Log::info("🔄 [Queue] Starting processing for document {$documentId}", [
            //     'attempt' => $this->attempts() + 1,
            //     'max_attempts' => $this->tries,
            //     'document_title' => $document->title,
            //     'file_size' => $document->file_size
            // ]);

            // log awal

            // Set initial processing state
            $document->update([
                'status' => 'processing',
                'processing_started_at' => now(),
                'processing_metadata' => [
                    'started_at' => now(),
                    'attempt' => $this->attempts() + 1,
                    'max_attempts' => $this->tries,
                    'process_id' => getmypid(),
                    'queue_name' => $this->queue ?? 'default'
                ]
            ]);

            // Step 1: Extract text (0% - 20%)
            // $this->updateProgress($progressKey, 5, 'Mengekstrak teks dari dokumen...', 'extracting_text');
            $this->updateProgress(
                progressKey: $progressKey,
                percent: 5,
                message: "Mengekstrak teks dari dokumen...",
                stage: "extracting_text"
            );

            $extractedText = $this->extractTextWithValidation($document, $textExtraction);
            $this->updateProgress(
                progressKey: $progressKey,
                percent: 10,
                message: "Selesai ekstrak teks dari dokumen...",
                stage: "extracting_text"
            );
            // Step 2: Prepare for TTS (20% - 25%)
            // $this->updateProgress($progressKey, 20, 'Mempersiapkan konversi audio...', 'preparing_tts');
            $this->updateProgress(
                progressKey: $progressKey,
                percent: 15,
                message: "Mempersiapkan teks (cleaning and validating) untuk dikonversi ke audio...",
                stage: "preparing_tts"
            );
            $this->validateTextForTTS($extractedText);
            $this->updateProgress(
                progressKey: $progressKey,
                percent: 20,
                message: "Selesai cleaning and validating teks untuk dikonversi ke audio...",
                stage: "preparing_tts"
            );

            // Step 3: Convert to audio with progress updates (25% - 85%)
            // $this->updateProgress($progressKey, 25, 'Memulai konversi audio...', 'tts_starting');
            $this->updateProgress(
                progressKey: $progressKey,
                percent: 25,
                message: "Memulai konversi ke audio...",
                stage: "tts_starting"
            );

            $audioFiles = $this->convertToAudioWithProgress($document, $ttsService, $extractedText, $progressKey);
            // $this->updateProgress(
            //     progressKey: $progressKey,
            //     percent: 80,
            //     message: "Selesai konversi ke audio...",
            //     stage: "tts_starting"
            // );

            // Step 4: Generate cover if needed (85% - 90%)
            // $this->updateProgress($progressKey, 85, 'Membuat cover dokumen...', 'generating_cover');
            $this->updateProgress(
                progressKey: $progressKey,
                percent: 80,
                message: "Membuat cover dokumen (kalau belum ada)...",
                stage: "generating_cover"
            );
            $this->generateCoverIfNeeded($document);

            // Step 5: Save final data (90% - 100%)
            // $this->updateProgress($progressKey, 90, 'Menyimpan hasil akhir...', 'saving_data');
            $this->updateProgress(
                progressKey: $progressKey,
                percent: 90,
                message: "Menyimpan hasil akhir...",
                stage: "saving_data"
            );
            $this->saveFinalResults($document, $audioFiles, $extractedText, $startTime);

            // $this->updateProgress($progressKey, 100, 'Proses selesai!', 'completed');
            $this->updateProgress(
                progressKey: $progressKey,
                percent: 100,
                message: "Alhamdulillah semua proses selesai!...",
                stage: "completed!"
            );

            // Keep progress for 10 minutes then clean up
            Cache::put($progressKey . '_completed', true, 600);

            $processingTime = round(microtime(true) - $startTime, 2);
        } catch (\Exception $e) {
            $this->handleProcessingError($document, $e, $progressKey, $startTime);
        }
    }

    private function validateDocumentState(Document $document): void
    {
        if (empty($document->file_path)) {
            throw new \Exception("Document has no file path");
        }

        if ($document->status === 'completed') {
            throw new \Exception("Document already completed");
        }

        // Check if another job is processing this document
        if (
            $document->status === 'processing' &&
            $document->processing_started_at &&
            $document->processing_started_at->diffInMinutes(now()) < 5
        ) {
            throw new \Exception("Document is already being processed by another job");
        }
    }

    private function extractTextWithValidation(Document $document, TextExtractionService $textExtraction): string
    {
        try {
            // Log::info("📄 [Queue] Extracting text from document {$document->id}");

            $disk = 'documents';
            $path = $document->file_path;              // e.g. '2025/10-document.pdf'

            // --- 1) Determine source + mime
            if ($path && Storage::disk($disk)->exists($path)) {
                // Prefer mime from DB; fallback to disk mime
                $mime = $document->file_mime_type ?: (Storage::disk($disk)->mimeType($path) ?? 'application/octet-stream');

                // If your service supports path/stream, use it to avoid loading whole file in memory:
                if (method_exists($textExtraction, 'extractFromPath')) {
                    $absolutePath = Storage::disk($disk)->path($path);
                    $extractedText = $textExtraction->extractFromPath($absolutePath, $mime);
                } elseif (method_exists($textExtraction, 'extractFromStream')) {
                    $stream = Storage::disk($disk)->readStream($path);
                    try {
                        $extractedText = $textExtraction->extractFromStream($stream, $mime);
                    } finally {
                        if (is_resource($stream)) fclose($stream);
                    }
                } else {
                    // Legacy signature: content + mime
                    $content = Storage::disk($disk)->get($path);  // loads into memory
                    $extractedText = $textExtraction->extract($content, $mime);
                }
            } elseif (!empty($document->file_content)) {
                // --- 2) Legacy fallback (old rows still have file_content)
                $mime = $document->file_mime_type ?: 'application/octet-stream';
                $extractedText = $textExtraction->extract($document->file_content, $mime);
            } else {
                throw new \Exception("File not found on disk and no legacy file_content present.");
            }

            // --- 3) Validate result
            if (empty($extractedText)) {
                throw new \Exception("Text extraction returned empty result");
            }

            $textLength = strlen($extractedText);
            $wordCount  = str_word_count($extractedText);

            Log::info("✅ [Queue] Text extracted successfully", [
                'text_length' => $textLength,
                'word_count'  => $wordCount,
            ]);

            // --- 4) Persist result
            $document->update([
                'extracted_text'     => $extractedText,
                'text_extracted_at'  => now(),
            ]);

            return $extractedText;
        } catch (\Exception $e) {
            Log::error("❌ [Queue] Text extraction failed", [
                'error'     => $e->getMessage(),
                'file_type' => $document->file_mime_type,
                'file_size' => $document->file_size,
                'disk'      => 'documents',
                'path'      => $document->file_path,
            ]);

            // protected PDF heuristics (keep yours)
            $msg = strtolower($e->getMessage());
            if (
                str_contains($msg, 'secured') || str_contains($msg, 'password') ||
                str_contains($msg, 'protected') || str_contains($msg, 'encrypted')
            ) {

                $this->handleProtectedPdfError($document, $e->getMessage());
                throw new \Exception(
                    "PDF terproteksi: Dokumen memiliki proteksi keamanan dan memerlukan tindakan manual. " .
                        "Silakan hapus proteksi PDF atau konversi ke format lain sebelum upload ulang."
                );
            }

            throw new \Exception("Text extraction failed: " . $e->getMessage());
        }
    }
    private function validateTextForTTS(string $text): void
    {
        $textLength = strlen($text);
        $wordCount = str_word_count($text);

        if ($textLength < 10) {
            // throw new \Exception("Extracted text too short for TTS conversion");
            $this->logConversion(
                status: 'error',
                stage: 'validate_tts',
                message: 'Isi dokumen terlalu sedikit (kurang dari 10 karakter).',
                meta: [
                    'document_id' => $this->documentId,
                ],
            );
        }

        if ($textLength > 500000) { // 500KB limit
            $this->logConversion(
                status: 'error',
                stage: 'validate_tts',
                message: 'Isi dokumen terlalu besar (kurang dari 500.000 karakter).',
                meta: [
                    'document_id' => $this->documentId,
                ],
            );
        }
    }

    private function convertToAudioWithProgress(Document $document, $ttsService, $extractedText, $progressKey): array
    {
        try {
            // Log::info("🎙️ [Queue] Starting TTS conversion for document {$document->id}");

            // Estimate chunks for better progress tracking
            $estimatedChunks = max(1, ceil(strlen($extractedText) / 500));
            $progressPerChunk = 60 / $estimatedChunks; // 60% total for TTS (25% - 85%)

            // $this->updateProgress($progressKey, 25, "Memulai konversi audio (estimasi {$estimatedChunks} bagian)...", 'tts_starting');

            $this->updateProgress(
                progressKey: $progressKey,
                percent: 25,
                message: "Memulai konversi ke audio (estimasi {$estimatedChunks} bagian)...",
                stage: "tts_starting"
            );

            // Create progress callback with better error handling
            $progressCallback = function ($chunkIndex, $totalChunks, $chunkStatus) use ($progressKey, $progressPerChunk, $document) {
                try {
                    $currentProgress = 25 + ($chunkIndex * $progressPerChunk);
                    $message = "Konversi audio bagian " . ($chunkIndex + 1) . "/{$totalChunks}";

                    if ($chunkStatus === 'failed') {
                        $message .= " - Ada kesalahan, mencoba ulang...";
                    } elseif ($chunkStatus === 'completed') {
                        $message .= " - Selesai";
                    }

                    // $this->updateProgress($progressKey, min($currentProgress, 84), $message, 'tts_processing');
                    $this->updateProgress(
                        progressKey: $progressKey,
                        percent: min($currentProgress, 84),
                        message: $message,
                        stage: "tts_processing"
                    );

                    // Update document metadata with current progress
                    try {
                        $document->update([
                            'processing_metadata' => array_merge($document->processing_metadata ?? [], [
                                'current_chunk' => $chunkIndex + 1,
                                'total_chunks' => $totalChunks,
                                'chunk_status' => $chunkStatus,
                                'last_update' => now()
                            ])
                        ]);
                    } catch (\Exception $e) {
                        // Ignore database update errors during progress
                        Log::debug("Progress update skipped: " . $e->getMessage());
                    }
                } catch (\Exception $e) {
                    Log::warning("⚠️ [Queue] Progress callback error: " . $e->getMessage());
                }
            };

            // Convert with progress tracking
            $audioFiles = $ttsService->convertToAudioWithProgress($extractedText, $progressCallback);

            if (empty($audioFiles['mp3'])) {
                throw new \Exception("TTS conversion completed but no MP3 data generated");
            }

            Log::info("✅ [Queue] TTS conversion completed", [
                'mp3_size' => strlen($audioFiles['mp3']),
                'flac_size' => $audioFiles['flac'] ? strlen($audioFiles['flac']) : 0,
                'duration' => $audioFiles['duration']
            ]);

            return $audioFiles;
        } catch (\Exception $e) {
            Log::error("❌ [Queue] TTS conversion failed", [
                'error' => $e->getMessage(),
                'text_preview' => substr($extractedText, 0, 200)
            ]);
            throw new \Exception("TTS conversion failed: " . $e->getMessage());
        }
    }

    private function generateCoverIfNeeded(Document $document): void
    {
        if (!$document->cover_image) {
            try {
                Log::info("🖼️ [Queue] Generating cover for document {$document->id}");

                // Only generate cover for PDFs
                if ($document->file_mime_type === 'application/pdf') {
                    $coverData = $this->generateCoverFromPDF();
                    if ($coverData) {
                        $document->update([
                            'cover_image' => $coverData,
                            'cover_mime_type' => 'image/jpeg',
                        ]);
                        Log::info("✅ [Queue] Cover generated successfully");
                    }
                } else {
                    Log::info("ℹ️ [Queue] Cover generation skipped for non-PDF document");
                }
            } catch (\Exception $e) {
                Log::warning("⚠️ [Queue] Cover generation failed, continuing without cover", [
                    'error' => $e->getMessage()
                ]);
                // Don't throw - continue without cover
            }
        }
    }

    private function saveFinalResults(Document $document, array $audioFiles, string $extractedText, float $startTime): void
    {
        // The $audioFiles array should now contain the PATH to the temporary files.
        // Example: $audioFiles = ['mp3' => '/path/to/temp/combined_12345.mp3', 'duration' => 57.34]

        // --- 1. Define final storage location ---
        $disk = 'public'; // Or 's3', 'local', etc.
        // Create a unique destination path within the storage disk (e.g., documents/15/audio.mp3)
        $destinationPath = 'documents/' . $document->id . '/audio.mp3';

        // --- 2. Move / Store the MP3 file ---
        $mp3LocalPath = $audioFiles['mp3']; // This is the full local path from the combineAudioSegments function

        if (!file_exists($mp3LocalPath)) {
            throw new \Exception("MP3 file not found at expected temporary path: {$mp3LocalPath}");
        }

        // Move the file from the temporary location to the final storage disk
        // The Storage::disk('public') knows how to move files (local to local, or local to S3, etc.)
        $mp3StoredPath = Storage::disk($disk)->put($destinationPath, file_get_contents($mp3LocalPath));

        // Get file size for metadata before deleting temp file
        $mp3Size = Storage::disk($disk)->size($destinationPath);

        // Clean up the temporary file (important!)
        @unlink($mp3LocalPath);

        // FLAC file logic (assuming you have a similar path for FLAC if needed)
        $flacStoredPath = null;
        $flacSize = 0;
        // You would repeat the storage/cleanup logic here for the FLAC file if you are generating it.

        try {
            $processingTime = round(microtime(true) - $startTime, 2);

            $document->update([
                // --- UPDATED: Save the relative path instead of file content ---
                'mp3_path' => $destinationPath,
                'flac_path' => $flacStoredPath, // Assuming you added a flac_path column too
                // -----------------------------------------------------------------
                'audio_duration' => $audioFiles['duration'],
                'status' => 'completed',
                'processing_completed_at' => now(),
                'processing_metadata' => array_merge($document->processing_metadata ?? [], [
                    'processed_at' => now(),
                    'file_type' => $document->file_mime_type,
                    'text_length' => strlen($extractedText),
                    'audio_format' => 'mp3/flac',
                    'processing_time_seconds' => $processingTime,
                    'processed_via' => 'queue',
                    'audio_duration_seconds' => $audioFiles['duration'],
                    'final_attempt' => $this->attempts() + 1,
                    // --- UPDATED: Use the file size instead of content length ---
                    'mp3_size' => $mp3Size,
                    'flac_size' => $flacSize,
                    // -----------------------------------------------------------
                    'completion_status' => 'success',
                    'storage_disk' => $disk, // Log which disk was used
                ])
            ]);

            Log::info("💾 [Queue] Final results saved successfully", [
                'processing_time' => $processingTime,
                'audio_duration' => $audioFiles['duration'],
                'mp3_size' => $mp3Size,
                'mp3_path' => $destinationPath, // Log the path
            ]);
        } catch (\Exception $e) {
            Log::error("❌ [Queue] Failed to save final results", [
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Failed to save final results: " . $e->getMessage());
        }
    }
    private function handleProcessingError(Document $document, \Exception $e, string $progressKey, float $startTime): void
    {
        $processingTime = round(microtime(true) - $startTime, 2);
        $currentAttempt = $this->attempts() + 1;
        $isLastAttempt  = $currentAttempt >= $this->tries;

        // Log ke file tetap boleh, biar dev punya jejak lengkap di laravel.log
        Log::error("❌ [Queue] Document {$document->id} processing failed", [
            'error'            => $e->getMessage(),
            'attempt'          => $currentAttempt,
            'max_attempts'     => $this->tries,
            'is_last_attempt'  => $isLastAttempt,
            'processing_time'  => $processingTime,
        ]);

        // 🔹 Update progress + log ke database
        $this->updateProgress(
            progressKey: $progressKey,
            percent: -1,
            message: 'Error: ' . $e->getMessage(),
            stage: $isLastAttempt ? 'failed_permanently' : 'failed_retrying',
            status: 'error',
            meta: [
                'document_id'        => $document->id,
                'attempt'            => $currentAttempt,
                'max_attempts'       => $this->tries,
                'is_last_attempt'    => $isLastAttempt,
                'processing_time'    => $processingTime,
                'exception_message'  => $e->getMessage(),
                // kalau mau, bisa tambahkan:
                // 'exception_trace' => $e->getTraceAsString(),
            ]
        );

        // Update document dengan info error (seperti sebelumnya)
        $errorMetadata = array_merge($document->processing_metadata ?? [], [
            'error'                     => $e->getMessage(),
            'failed_at'                 => now(),
            'failed_attempt'            => $currentAttempt,
            'processing_time_seconds'   => $processingTime,
            'processed_via'             => 'queue',
        ]);

        if ($isLastAttempt) {
            // Final failure - mark as failed
            $document->update([
                'status'              => 'failed',
                'processing_metadata' => array_merge($errorMetadata, [
                    'final_status'          => 'failed',
                    'all_attempts_exhausted' => true,
                ]),
            ]);

            Log::error("💥 [Queue] Document {$document->id} permanently failed after {$this->tries} attempts");
        } else {
            // Temporary failure - reset to pending for retry
            $document->update([
                'status'                => 'pending',
                'processing_started_at' => null,
                'processing_metadata'   => array_merge($errorMetadata, [
                    'will_retry'   => true,
                    'next_attempt' => $currentAttempt + 1,
                ]),
            ]);

            Log::warning("🔄 [Queue] Document {$document->id} will be retried (attempt {$currentAttempt}/{$this->tries})");
        }

        // Tetap lempar error supaya mekanisme retry Laravel jalan
        throw $e;
    }

    private function cleanupAndDelete(): void
    {
        // Clean up any progress cache
        $progressKey = "document_progress_{$this->documentId}";
        Cache::forget($progressKey);
        Cache::forget($progressKey . '_completed');

        // Delete the job
        $this->delete();
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("💀 [Queue] ProcessDocumentJob permanently failed", [
            'document_id' => $this->documentId,
            'error' => $exception->getMessage(),
            'final_attempt' => $this->attempts()
        ]);

        // Try to update document status if it still exists
        try {
            $document = Document::find($this->documentId);
            if ($document) {
                $document->update([
                    'status' => 'failed',
                    'processing_metadata' => array_merge($document->processing_metadata ?? [], [
                        'permanently_failed_at' => now(),
                        'final_error' => $exception->getMessage(),
                        'total_attempts' => $this->attempts()
                    ])
                ]);
            }
        } catch (\Exception $e) {
            Log::error("❌ [Queue] Failed to update document status after permanent failure", [
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function updateProgress(
        string $progressKey,
        int $percent,
        string $message,
        ?string $stage = null,
        string $status = 'info',
        array $meta = []
    ) {
        // 1. Update progress ke cache (tetap)
        Cache::put($progressKey, [
            'percent' => $percent,
            'message' => $message,
            'stage'   => $stage,
            'time'    => now(),
        ], 1800);

        // 2. Logging ke database (pakai trait LogsDocumentConversion)
        $this->logConversion(
            status: $status,       // info|success|error
            stage: $stage ?? 'progress_update',
            message: $message,
            meta: array_merge($meta, [
                'percent' => $percent,
                'progress_key' => $progressKey,
            ])
        );
    }


    private function generateCoverFromPDF(): ?string
    {
        try {
            // Placeholder for PDF cover generation
            Log::info("ℹ️ [Queue] PDF cover generation not implemented yet");
            return null;
        } catch (\Exception $e) {
            Log::warning("⚠️ [Queue] PDF cover generation failed: " . $e->getMessage());
            return null;
        }
    }

    public function retryUntil()
    {
        return now()->addHours(24);
    }

    public function tags(): array
    {
        return [
            'document-processing',
            "document-{$this->documentId}",
            "attempt-{$this->attempts()}"
        ];
    }

    private function handleProtectedPdfError(Document $document, string $errorMessage): void
    {
        // Mark document with specific error type
        $document->update([
            'status' => 'failed',
            'processing_metadata' => array_merge($document->processing_metadata ?? [], [
                'error_type' => 'pdf_protected',
                'error_message' => $errorMessage,
                'failed_at' => now(),
                'user_action_required' => true,
                'suggested_solutions' => [
                    'remove_pdf_protection',
                    'convert_to_word',
                    'print_to_unprotected_pdf',
                    'contact_admin'
                ]
            ])
        ]);
    }
}
