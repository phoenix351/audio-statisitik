<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TextExtractionService
{
    protected array $aiApiKeys;
    protected string $aiApiUrl;
    protected int $currentKeyIndex = 0;

    public function __construct()
    {
        $rawKeys = config('services.ai.api_key', '');
        $this->aiApiKeys = array_filter(array_map('trim', explode(',', $rawKeys)));

        if (empty($this->aiApiKeys)) {
            throw new \Exception("❌ Tidak ada AI_API_KEY terdeteksi di .env");
        }

        $this->aiApiUrl = rtrim(
            config('services.ai.api_url', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent'),
            '/'
        );
    }

    private function getCurrentApiKey(): string
    {
        return $this->aiApiKeys[$this->currentKeyIndex];
    }

    private function switchApiKey(): void
    {
        $old = $this->currentKeyIndex;
        $this->currentKeyIndex = ($this->currentKeyIndex + 1) % count($this->aiApiKeys);
        Log::warning("🔄 Switching AI API Key {$old} → {$this->currentKeyIndex}");
    }

    public function extract(string $fileContent, string $mimeType): string
    {
        try {
            // Check if PDF is password protected before processing
            if ($mimeType === 'application/pdf') {
                $protectionInfo = $this->checkPdfProtection($fileContent);
                if ($protectionInfo['is_protected']) {
                    Log::warning("🔒 Protected PDF detected", $protectionInfo);

                    // Try to handle protected PDF
                    $rawText = $this->handleProtectedPdf($fileContent, $protectionInfo);
                } else {
                    $rawText = $this->extractRawText($fileContent, $mimeType);
                }
            } else {
                $rawText = $this->extractRawText($fileContent, $mimeType);
            }

            $cleanText = $this->sanitizeText($rawText);
            Log::info('✅ Text extracted successfully', [
                'original_length' => strlen($rawText),
                'cleaned_length' => strlen($cleanText)
            ]);

            return $this->filterImportantText($cleanText);
        } catch (\Exception $e) {
            Log::error('❌ Text extraction failed: ' . $e->getMessage());
            throw $e;
        }
    }
    public function extractFromPath(string $absolutePath, string $mimeType): string
    {
        // If your low-level extractors can read paths directly, use them.
        // If they require content, read in chunks or to temp.
        $content = file_get_contents($absolutePath); // OK for moderate files
        // For really huge files, switch to streams (see extractFromStream)
        return $this->extract($content, $mimeType);
    }

    /**
     * Most memory-friendly: accept a stream resource.
     * @param resource $stream
     */
    public function extractFromStream($stream, string $mimeType): string
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('Stream is not a valid resource');
        }

        // If your libs need a file path, write stream to a temp file safely:
        $tmp = tmpfile();
        $meta = stream_get_meta_data($tmp);
        $tmpPath = $meta['uri'];

        // Copy stream → temp file in chunks
        stream_copy_to_stream($stream, $tmp);

        // Now reuse the path-based flow
        try {
            return $this->extractFromPath($tmpPath, $mimeType);
        } finally {
            // tmpfile() auto-deletes on fclose
            fclose($tmp);
        }
    }

    private function checkPdfProtection(string $fileContent): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_check_');
        file_put_contents($tempFile, $fileContent);

        try {
            // Method 1: Try with PdfParser to detect protection
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($tempFile);
                $text = $pdf->getText();

                // If we get here without exception, PDF is not protected
                unlink($tempFile);
                return [
                    'is_protected' => false,
                    'protection_type' => 'none',
                    'can_extract_text' => true
                ];
            } catch (\Exception $e) {
                $errorMessage = strtolower($e->getMessage());

                if (
                    strpos($errorMessage, 'secured') !== false ||
                    strpos($errorMessage, 'password') !== false ||
                    strpos($errorMessage, 'encrypted') !== false ||
                    strpos($errorMessage, 'protected') !== false
                ) {

                    // Try to determine protection level
                    $protectionLevel = $this->analyzePdfProtectionLevel($fileContent);

                    unlink($tempFile);
                    return [
                        'is_protected' => true,
                        'protection_type' => $protectionLevel,
                        'can_extract_text' => false,
                        'error_message' => $e->getMessage()
                    ];
                }

                // Other parsing errors
                throw $e;
            }
        } catch (\Exception $e) {
            unlink($tempFile);
            throw $e;
        }
    }

    private function analyzePdfProtectionLevel(string $fileContent): string
    {
        // Check for common PDF security markers in the raw content
        $content = substr($fileContent, 0, 10000); // Check first 10KB

        if (strpos($content, '/Encrypt') !== false) {
            if (strpos($content, '/V 1') !== false) {
                return 'rc4_40bit';
            } elseif (strpos($content, '/V 2') !== false) {
                return 'rc4_128bit';
            } elseif (strpos($content, '/V 4') !== false) {
                return 'aes_128bit';
            } elseif (strpos($content, '/V 5') !== false) {
                return 'aes_256bit';
            }
            return 'encrypted_unknown';
        }

        return 'permission_restricted';
    }

    private function handleProtectedPdf(string $fileContent, array $protectionInfo): string
    {
        Log::info("🔓 Attempting to handle protected PDF", $protectionInfo);

        // Strategy 1: Try OCR-based extraction
        try {
            return $this->extractTextWithOcr($fileContent);
        } catch (\Exception $e) {
            Log::warning("⚠️ OCR extraction failed: " . $e->getMessage());
        }

        // Strategy 2: Try alternative PDF libraries
        try {
            return $this->extractWithAlternativeLibraries($fileContent);
        } catch (\Exception $e) {
            Log::warning("⚠️ Alternative library extraction failed: " . $e->getMessage());
        }

        // Strategy 3: Try with external tools
        try {
            return $this->extractWithExternalTools($fileContent);
        } catch (\Exception $e) {
            Log::warning("⚠️ External tool extraction failed: " . $e->getMessage());
        }

        // Strategy 4: Provide user-friendly error with suggestions
        throw new \Exception($this->generateUserFriendlyError($protectionInfo));
    }

    private function extractTextWithOcr(string $fileContent): string
    {
        // This would require Tesseract OCR and ImageMagick
        // For now, throw exception with suggestion
        throw new \Exception("OCR extraction not implemented yet");
    }

    private function extractWithAlternativeLibraries(string $fileContent): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_alt_');
        file_put_contents($tempFile, $fileContent);

        try {
            // Try with TCPDF Parser (if available)
            if (class_exists('\TCPDF_PARSER')) {
                $parser = new \TCPDF_PARSER($tempFile);
                $text = $parser->getText();
                unlink($tempFile);
                return $text;
            }

            // Try with other methods...
            throw new \Exception("No alternative PDF libraries available");
        } catch (\Exception $e) {
            unlink($tempFile);
            throw $e;
        }
    }

    private function extractWithExternalTools(string $fileContent): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_ext_');
        file_put_contents($tempFile, $fileContent);

        try {
            // Try with pdftotext (if available)
            $output = shell_exec("pdftotext \"$tempFile\" - 2>&1");

            if ($output && !empty(trim($output)) && strpos($output, 'Error') === false) {
                unlink($tempFile);
                return $output;
            }

            // Try with other command-line tools
            $output = shell_exec("pdftk \"$tempFile\" output - uncompress 2>&1");

            if ($output && !empty(trim($output)) && strpos($output, 'Error') === false) {
                unlink($tempFile);
                // Would need additional processing here
                return $this->extractTextFromUncompressedPdf($output);
            }

            throw new \Exception("External tools failed or not available");
        } catch (\Exception $e) {
            unlink($tempFile);
            throw $e;
        }
    }

    private function extractTextFromUncompressedPdf(string $content): string
    {
        // Basic text extraction from uncompressed PDF content
        // This is a simplified approach
        $text = '';

        // Extract text between BT and ET markers
        preg_match_all('/BT\s+(.*?)\s+ET/s', $content, $matches);

        foreach ($matches[1] as $textBlock) {
            // Extract strings in parentheses
            preg_match_all('/\((.*?)\)/', $textBlock, $stringMatches);
            foreach ($stringMatches[1] as $string) {
                $text .= $string . ' ';
            }
        }

        return trim($text);
    }

    private function generateUserFriendlyError(array $protectionInfo): string
    {
        $baseMessage = "Dokumen PDF yang Anda upload memiliki proteksi keamanan dan tidak dapat diproses secara otomatis.";

        $suggestions = [
            "📝 **Solusi yang disarankan:**",
            "1. **Hapus proteksi PDF** menggunakan software seperti:",
            "   • Adobe Acrobat (Remove Security)",
            "   • PDFtk (command line tool)",
            "   • SmallPDF.com (online tool)",
            "   • ILovePDF.com (online tool)",
            "",
            "2. **Atau konversi ke format lain:**",
            "   • Export sebagai Word (.docx) lalu upload",
            "   • Print to PDF tanpa security",
            "   • Scan ulang sebagai PDF biasa",
            "",
            "3. **Atau hubungi admin** untuk bantuan manual processing"
        ];

        if ($protectionInfo['protection_type'] === 'permission_restricted') {
            $suggestions[] = "";
            $suggestions[] = "ℹ️ **Info:** PDF ini memiliki pembatasan permission tetapi mungkin bisa dibuka. Coba cara di atas.";
        } elseif (strpos($protectionInfo['protection_type'], 'encrypted') !== false) {
            $suggestions[] = "";
            $suggestions[] = "🔐 **Info:** PDF ini terenkripsi dengan password. Pastikan untuk menghapus password terlebih dahulu.";
        }

        return $baseMessage . "\n\n" . implode("\n", $suggestions);
    }

    private function extractRawText(string $fileContent, string $mimeType): string
    {
        switch ($mimeType) {
            case 'application/pdf':
                return $this->extractFromPdf($fileContent);
            case 'application/msword':
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                return $this->extractFromDoc($fileContent);
            default:
                throw new \Exception('Unsupported file format: ' . $mimeType);
        }
    }

    private function extractFromPdf(string $fileContent): string
    {
        // Validasi apakah file benar-benar PDF
        if (substr($fileContent, 0, 4) !== '%PDF') {
            throw new \Exception("File bukan PDF yang valid");
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_');
        file_put_contents($tempFile, $fileContent); // Jangan ubah $fileContent!

        try {
            // Coba ekstraksi dengan spipu/html-to-pdf parser
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($tempFile);
            $text = $pdf->getText();

            // Baru sanitize SETELAH ekstraksi teks berhasil
            $text = $this->sanitizeText($text);

            unlink($tempFile);
            return $text;
        } catch (\Exception $e) {
            unlink($tempFile);

            // Coba metode alternatif jika parser gagal
            if (strpos($e->getMessage(), 'Invalid object reference') !== false) {
                return $this->tryAlternativePdfExtraction($fileContent);
            }

            throw new \Exception('PDF extraction failed: ' . $e->getMessage());
        }
    }

    private function tryAlternativePdfExtraction(string $fileContent): string
    {
        // Metode cadangan menggunakan command line tools
        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_alt_');
        file_put_contents($tempFile, $fileContent);

        try {
            // Coba pdftotext jika tersedia
            $output = shell_exec("pdftotext '$tempFile' - 2>/dev/null");
            if (!empty($output)) {
                unlink($tempFile);
                return $this->sanitizeText($output);
            }

            unlink($tempFile);
            throw new \Exception("Alternatif PDF extraction juga gagal");
        } catch (\Exception $e) {
            unlink($tempFile);
            throw $e;
        }
    }

    private function extractFromDoc(string $fileContent): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'doc_extract_');
        file_put_contents($tempFile, $fileContent);

        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($tempFile);
            $text = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    } elseif (method_exists($element, 'getElements')) {
                        foreach ($element->getElements() as $childElement) {
                            if (method_exists($childElement, 'getText')) {
                                $text .= $childElement->getText() . "\n";
                            }
                        }
                    }
                }
            }

            unlink($tempFile);

            if (empty(trim($text))) {
                throw new \Exception("Document appears to be empty");
            }

            return $text;
        } catch (\Exception $e) {
            unlink($tempFile);
            throw new \Exception('Failed to extract text from DOC: ' . $e->getMessage());
        }
    }

    private function sanitizeText(string $text): string
    {
        Log::info('🧹 Starting text sanitization', ['original_length' => strlen($text)]);

        // Detect encoding and convert to UTF-8
        $encoding = mb_detect_encoding($text, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);

        if ($encoding && $encoding !== 'UTF-8') {
            $text = mb_convert_encoding($text, 'UTF-8', $encoding);
        }

        // Force ke UTF-8
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $text = iconv('UTF-8', 'UTF-8//IGNORE', $text);
        // Hapus byte invalid yang nyasar (misalnya \xE2 sendirian)
        $text = preg_replace('/[\xC0-\xDF](?![\x80-\xBF])/', '', $text); // potongan 2-byte invalid
        $text = preg_replace('/[\xE0-\xEF](?!([\x80-\xBF]{2}))/', '', $text); // potongan 3-byte invalid
        $text = preg_replace('/[\xF0-\xF7](?!([\x80-\xBF]{3}))/', '', $text); // potongan 4-byte invalid

        // Remove invalid UTF-8 replacement chars (�) and control characters (except \n, \t)
        $text = preg_replace('/\xEF\xBF\xBD/u', '', $text); // hapus � (U+FFFD)
        $text = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $text);
        $text = preg_replace('/[\x00-\x1F\x7F]/u', '', $text);

        // Normalize whitespace
        $text = preg_replace('/\r\n?/', "\n", $text);     // CRLF → LF
        $text = preg_replace('/[ \t]+/', ' ', $text);     // tab/space berlebih → 1 spasi
        $text = preg_replace('/\n{3,}/', "\n\n", $text);  // lebih dari 2 newline → 2 newline

        $isValidUtf8 = mb_check_encoding($text, 'UTF-8');
        Log::info('✅ Text sanitization completed', [
            'cleaned_length' => strlen($text),
            'is_valid_utf8' => $isValidUtf8
        ]);

        return trim($text);
    }

    private function filterImportantText(string $rawText): string
    {
        if (!empty($this->aiApiKeys)) {
            try {
                return $this->aiFilterText($rawText);
            } catch (\Exception $e) {
                Log::warning('⚠️ AI text filtering failed, using basic cleaning: ' . $e->getMessage());
            }
        }
        return $this->basicTextCleaning($rawText);
    }

    private function aiFilterText(string $rawText): string
    {
        $chunks = $this->splitTextIntoChunks($rawText, 3000);
        $filteredChunks = [];

        Log::info('🤖 Starting AI text filtering', [
            'raw_length' => strlen($rawText),
            'chunks_count' => count($chunks)
        ]);

        foreach ($chunks as $i => $chunk) {
            $attempts = 0;
            $maxAttempts = count($this->aiApiKeys);

            while ($attempts < $maxAttempts) {
                $apiKey = $this->getCurrentApiKey();
                Log::debug("📤 Sending chunk {$i} to Gemini (API Key {$this->currentKeyIndex})");

                $prompt = "Saya akan memberikan teks mentah dari dokumen statistik BPS yang mungkin berisi header, footer, nomor halaman, daftar isi, daftar tabel, daftar lampiran, gambar, infografis, dan tabel data numerik.
                
                Tugas Anda: Ekstrak SELURUH teks PARAGRAF UTAMA dengan mempertahankan struktur dan isi kalimat PERSIS seperti aslinya.
                ➤ Pertahankan urutan paragraf sesuai aslinya. Jangan melakukan parafrasa, ringkasan, atau perubahan susunan kalimat.

                INSTRUKSI:
                1. Abaikan header, footer, nomor halaman, dan metadata dokumen
                2. Abaikan daftar isi, daftar tabel, daftar lampiran, dan daftar gambar
                3. Abaikan tabel-tabel yang hanya berisi angka/statistik tanpa penjelasan
                4. Abaikan keterangan atau footer pada tabel
                5. Hapus semua elemen visual: gambar, infografis, diagram, dan ilustrasi.
                6. Pastikan hasil cocok untuk konversi text-to-speech (mudah dibaca). Namun jangan mengubah struktur kalimat asli
                7. Hapus karakter khusus yang tidak perlu.";

                try {
                    $response = Http::timeout(30)->post($this->aiApiUrl . '?key=' . $apiKey, [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],  // instruksi
                                    ['text' => $chunk]    // <— ini teks hasil ekstraksi PDF
                                ]
                            ]
                        ],
                        'generationConfig' => [
                            'temperature' => 0.1,
                            'maxOutputTokens' => 2048
                        ]
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $parts = $data['candidates'][0]['content']['parts'] ?? [];
                        $texts = [];
                        foreach ($parts as $p) {
                            if (isset($p['text'])) $texts[] = $p['text'];
                        }
                        $filteredText = trim(implode("\n", $texts));
                        $filteredChunks[] = $filteredText !== '' ? $filteredText : $this->basicTextCleaning($chunk);
                        break;
                    } else {
                        Log::warning('⚠️ Gemini response failed', [
                            'chunk_index' => $i,
                            'status' => $response->status(),
                            'body' => substr($response->body(), 0, 500)
                        ]);

                        // Switch API key for certain errors
                        if (in_array($response->status(), [400, 403, 429])) {
                            $this->switchApiKey();
                            $attempts++;
                            continue;
                        } else {
                            $filteredChunks[] = $this->basicTextCleaning($chunk);
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("⚠️ AI filtering error for chunk {$i}: " . $e->getMessage());
                    $filteredChunks[] = $this->basicTextCleaning($chunk);
                    break;
                }

                $attempts++;
            }

            usleep(500000); // 0.5 second delay between chunks
        }

        $finalText = implode("\n\n", $filteredChunks);
        Log::info('✅ AI text filtering completed', [
            'final_length' => strlen($finalText),
            'chunks_processed' => count($filteredChunks)
        ]);

        return $finalText;
    }

    private function splitTextIntoChunks(string $text, int $maxLength): array
    {
        $sentences = preg_split('/(?<=[\.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $chunks = [];
        $current = '';

        foreach ($sentences as $s) {
            if (strlen($current . $s) <= $maxLength) {
                $current .= $s . ' ';
            } else {
                if ($current !== '') $chunks[] = trim($current);
                $current = $s . ' ';
            }
        }
        if ($current !== '') $chunks[] = trim($current);

        return $chunks ?: [$text];
    }

    private function basicTextCleaning(string $text): string
    {
        // Remove page numbers and headers
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/\bHalaman \d+\b/i', '', $text);
        $text = preg_replace('/\b\d+\s*$/m', '', $text);

        // Remove long numeric sequences (likely tables)
        $text = preg_replace('/(\d+[\s,\.]+){4,}/', '', $text);

        // Remove BPS headers/footers
        $text = preg_replace('/\b(BPS|Badan Pusat Statistik|Sulawesi Utara|SULUT)\s+(Provinsi|Province)?\s*\d*/i', '', $text);

        // Remove ISBN/ISSN
        $text = preg_replace('/\b(ISBN|ISSN)\s*:?\s*[\d\-X]+/i', '', $text);

        // Remove email and URLs
        $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '', $text);
        $text = preg_replace('/https?:\/\/[^\s]+/', '', $text);

        // Remove repeated words
        $text = preg_replace('/\b(\w+)\s+\1\b/i', '$1', $text);

        // Clean up excessive dots and spaces
        $text = preg_replace('/\.{3,}/', '...', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}