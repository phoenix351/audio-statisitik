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

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
                Log::warning("🗑️ [Queue] Document {$this->documentId} not found in database, job will be deleted", [
                    'document_id' => $this->documentId,
                    'job_id' => $this->job->getJobId() ?? 'unknown'
                ]);
                
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
            Log::warning("🗑️ [Queue] Document {$this->documentId} model not found during processing", [
                'error' => $e->getMessage(),
                'document_id' => $this->documentId
            ]);
            
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
            
            $this->updateProgress($progressKey, 0, 'Memulai proses...', 'initializing');
            
            Log::info("🔄 [Queue] Starting processing for document {$documentId}", [
                'attempt' => $this->attempts() + 1,
                'max_attempts' => $this->tries,
                'document_title' => $document->title,
                'file_size' => $document->file_size
            ]);

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
            $this->updateProgress($progressKey, 5, 'Mengekstrak teks dari dokumen...', 'extracting_text');
            $extractedText = $this->extractTextWithValidation($document, $textExtraction);
            
            // Step 2: Prepare for TTS (20% - 25%)
            $this->updateProgress($progressKey, 20, 'Mempersiapkan konversi audio...', 'preparing_tts');
            $this->validateTextForTTS($extractedText);
            
            // Step 3: Convert to audio with progress updates (25% - 85%)
            $this->updateProgress($progressKey, 25, 'Memulai konversi audio...', 'tts_starting');
            $audioFiles = $this->convertToAudioWithProgress($document, $ttsService, $extractedText, $progressKey);

            // Step 4: Generate cover if needed (85% - 90%)
            $this->updateProgress($progressKey, 85, 'Membuat cover dokumen...', 'generating_cover');
            $this->generateCoverIfNeeded($document);

            // Step 5: Save final data (90% - 100%)
            $this->updateProgress($progressKey, 90, 'Menyimpan hasil akhir...', 'saving_data');
            $this->saveFinalResults($document, $audioFiles, $extractedText, $startTime);

            $this->updateProgress($progressKey, 100, 'Proses selesai!', 'completed');
            
            // Keep progress for 10 minutes then clean up
            Cache::put($progressKey . '_completed', true, 600);

            $processingTime = round(microtime(true) - $startTime, 2);
            Log::info("✅ [Queue] Document {$documentId} processing completed successfully", [
                'processing_time' => $processingTime,
                'attempt' => $this->attempts() + 1,
                'final_status' => 'completed'
            ]);

        } catch (\Exception $e) {
            $this->handleProcessingError($document, $e, $progressKey, $startTime);
        }
    }

    private function validateDocumentState(Document $document): void
    {
        if (empty($document->file_content)) {
            throw new \Exception("Document has no file content");
        }
        
        if ($document->status === 'completed') {
            throw new \Exception("Document already completed");
        }
        
        // Check if another job is processing this document
        if ($document->status === 'processing' && 
            $document->processing_started_at && 
            $document->processing_started_at->diffInMinutes(now()) < 5) {
            throw new \Exception("Document is already being processed by another job");
        }
    }

    private function extractTextWithValidation(Document $document, TextExtractionService $textExtraction): string
    {
        try {
            Log::info("📄 [Queue] Extracting text from document {$document->id}");
            
            $extractedText = $textExtraction->extract(
                $document->file_content,
                $document->file_mime_type
            );

            if (empty($extractedText)) {
                throw new \Exception("Text extraction returned empty result");
            }

            $textLength = strlen($extractedText);
            $wordCount = str_word_count($extractedText);

            Log::info("✅ [Queue] Text extracted successfully", [
                'text_length' => $textLength,
                'word_count' => $wordCount
            ]);

            // Save extracted text immediately
            $document->update(['extracted_text' => $extractedText]);
            
            return $extractedText;
            
        } catch (\Exception $e) {
            Log::error("❌ [Queue] Text extraction failed", [
                'error' => $e->getMessage(),
                'file_type' => $document->file_mime_type,
                'file_size' => $document->file_size
            ]);
            
            // Check if this is a PDF protection error
            $errorMessage = strtolower($e->getMessage());
            if (strpos($errorMessage, 'secured') !== false || 
                strpos($errorMessage, 'password') !== false ||
                strpos($errorMessage, 'protected') !== false ||
                strpos($errorMessage, 'encrypted') !== false) {
                
                $this->handleProtectedPdfError($document, $e->getMessage());
                throw new \Exception("PDF terproteksi: Dokumen memiliki proteksi keamanan dan memerlukan tindakan manual. Silakan hapus proteksi PDF atau konversi ke format lain sebelum upload ulang.");
            }
            
            throw new \Exception("Text extraction failed: " . $e->getMessage());
        }
    }

    private function validateTextForTTS(string $text): void
    {
        $textLength = strlen($text);
        $wordCount = str_word_count($text);
        
        if ($textLength < 10) {
            throw new \Exception("Extracted text too short for TTS conversion");
        }
        
        if ($textLength > 500000) { // 500KB limit
            Log::warning("⚠️ [Queue] Very large text detected", [
                'text_length' => $textLength,
                'word_count' => $wordCount
            ]);
        }
    }

    private function convertToAudioWithProgress(Document $document, $ttsService, $extractedText, $progressKey): array
    {
        try {
            Log::info("🎙️ [Queue] Starting TTS conversion for document {$document->id}");
            
            // Estimate chunks for better progress tracking
            $estimatedChunks = max(1, ceil(strlen($extractedText) / 500));
            $progressPerChunk = 60 / $estimatedChunks; // 60% total for TTS (25% - 85%)
            
            $this->updateProgress($progressKey, 25, "Memulai konversi audio (estimasi {$estimatedChunks} bagian)...", 'tts_starting');
            
            // Create progress callback with better error handling
            $progressCallback = function($chunkIndex, $totalChunks, $chunkStatus) use ($progressKey, $progressPerChunk, $document) {
                try {
                    $currentProgress = 25 + ($chunkIndex * $progressPerChunk);
                    $message = "Konversi audio bagian " . ($chunkIndex + 1) . "/{$totalChunks}";
                    
                    if ($chunkStatus === 'failed') {
                        $message .= " - Ada kesalahan, mencoba ulang...";
                    } elseif ($chunkStatus === 'completed') {
                        $message .= " - Selesai";
                    }
                    
                    $this->updateProgress($progressKey, min($currentProgress, 84), $message, 'tts_processing');
                    
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
        try {
            $processingTime = round(microtime(true) - $startTime, 2);
            
            $document->update([
                'mp3_content' => $audioFiles['mp3'],
                'flac_content' => $audioFiles['flac'],
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
                    'mp3_size' => strlen($audioFiles['mp3']),
                    'flac_size' => $audioFiles['flac'] ? strlen($audioFiles['flac']) : 0,
                    'completion_status' => 'success'
                ])
            ]);
            
            Log::info("💾 [Queue] Final results saved successfully", [
                'processing_time' => $processingTime,
                'audio_duration' => $audioFiles['duration'],
                'mp3_size' => strlen($audioFiles['mp3'])
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
        $isLastAttempt = $currentAttempt >= $this->tries;
        
        Log::error("❌ [Queue] Document {$document->id} processing failed", [
            'error' => $e->getMessage(),
            'attempt' => $currentAttempt,
            'max_attempts' => $this->tries,
            'is_last_attempt' => $isLastAttempt,
            'processing_time' => $processingTime
        ]);

        // Update progress with error
        $this->updateProgress($progressKey, -1, 'Error: ' . $e->getMessage(), 'failed');

        // Update document with error information
        $errorMetadata = array_merge($document->processing_metadata ?? [], [
            'error' => $e->getMessage(),
            'failed_at' => now(),
            'failed_attempt' => $currentAttempt,
            'processing_time_seconds' => $processingTime,
            'processed_via' => 'queue'
        ]);

        if ($isLastAttempt) {
            // Final failure - mark as failed
            $document->update([
                'status' => 'failed',
                'processing_metadata' => array_merge($errorMetadata, [
                    'final_status' => 'failed',
                    'all_attempts_exhausted' => true
                ])
            ]);
            
            Log::error("💥 [Queue] Document {$document->id} permanently failed after {$this->tries} attempts");
        } else {
            // Temporary failure - reset to pending for retry
            $document->update([
                'status' => 'pending',
                'processing_started_at' => null,
                'processing_metadata' => array_merge($errorMetadata, [
                    'will_retry' => true,
                    'next_attempt' => $currentAttempt + 1
                ])
            ]);
            
            Log::warning("🔄 [Queue] Document {$document->id} will be retried (attempt {$currentAttempt}/{$this->tries})");
        }

        // Always throw to trigger Laravel's retry mechanism
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

    private function updateProgress(string $progressKey, int $percentage, string $message, string $stage): void
    {
        try {
            $progressData = [
                'percentage' => $percentage,
                'message' => $message,
                'stage' => $stage,
                'updated_at' => now()->toISOString(),
                'document_id' => $this->documentId,
                'job_id' => $this->job->getJobId() ?? null
            ];
            
            Cache::put($progressKey, $progressData, 1800); // 30 minutes
            
        } catch (\Exception $e) {
            Log::warning("⚠️ [Queue] Failed to update progress", [
                'error' => $e->getMessage(),
                'progress_key' => $progressKey
            ]);
        }
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