<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class AudioController extends Controller
{
    /**
     * Get audio metadata for a document
     */
    public function getAudioMetadata(Request $request, $documentId): JsonResponse
    {
        try {
            $document = Document::findOrFail($documentId);
            
            // Check if user has permission to access this document
            if (!$this->canAccessDocument($document)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $metadata = $this->normalizeAudioMetadata($document);
            
            return response()->json([
                'success' => true,
                'data' => $metadata
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get audio metadata', [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load audio metadata'
            ], 500);
        }
    }

    /**
     * Stream audio content
     */
    public function streamAudio(Request $request, $documentId, $format = 'mp3'): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        try {
            $document = Document::findOrFail($documentId);
            
            // Check permissions
            if (!$this->canAccessDocument($document)) {
                abort(403);
            }

            // Validate format
            if (!in_array($format, ['mp3', 'flac'])) {
                abort(400, 'Invalid audio format');
            }

            // Get audio content
            $audioContent = $format === 'mp3' ? $document->mp3_content : $document->flac_content;
            
            if (empty($audioContent)) {
                abort(404, 'Audio content not found');
            }

            // Increment play count
            $this->incrementPlayCount($document);

            // Set headers for audio streaming
            $headers = [
                'Content-Type' => $format === 'mp3' ? 'audio/mpeg' : 'audio/flac',
                'Content-Length' => strlen($audioContent),
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=3600',
            ];

            // Handle range requests for better audio streaming
            $range = $request->header('Range');
            if ($range) {
                return $this->handleRangeRequest($audioContent, $range, $headers);
            }

            return response()->stream(function() use ($audioContent) {
                echo $audioContent;
            }, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Failed to stream audio', [
                'document_id' => $documentId,
                'format' => $format,
                'error' => $e->getMessage()
            ]);
            abort(500);
        }
    }

    /**
     * Get audio player data for multiple documents
     */
    public function getPlaylistData(Request $request): JsonResponse
    {
        try {
            $documentIds = $request->input('document_ids', []);
            
            if (empty($documentIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No document IDs provided'
                ], 400);
            }

            $documents = Document::whereIn('id', $documentIds)
                ->where('status', 'completed')
                ->where('is_active', true)
                ->get();

            $playlist = $documents->map(function($document) {
                if (!$this->canAccessDocument($document)) {
                    return null;
                }
                return $this->normalizeAudioMetadata($document);
            })->filter();

            return response()->json([
                'success' => true,
                'data' => $playlist->values()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get playlist data', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load playlist'
            ], 500);
        }
    }

    /**
     * Update playback progress
     */
    public function updateProgress(Request $request, $documentId): JsonResponse
    {
        try {
            $request->validate([
                'current_time' => 'required|numeric|min:0',
                'duration' => 'required|numeric|min:0'
            ]);

            $document = Document::findOrFail($documentId);
            
            if (!$this->canAccessDocument($document)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Store progress in cache (optional feature for resume playback)
            $userId = Auth::id() ?? 'guest_' . $request->ip();
            $progressKey = "audio_progress_{$userId}_{$documentId}";
            
            Cache::put($progressKey, [
                'current_time' => $request->current_time,
                'duration' => $request->duration,
                'updated_at' => now()
            ], 3600); // Store for 1 hour

            return response()->json([
                'success' => true,
                'message' => 'Progress updated'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update progress', [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update progress'
            ], 500);
        }
    }

    /**
     * Get playback progress
     */
    public function getProgress(Request $request, $documentId): JsonResponse
    {
        try {
            $userId = Auth::id() ?? 'guest_' . $request->ip();
            $progressKey = "audio_progress_{$userId}_{$documentId}";
            
            $progress = Cache::get($progressKey);

            return response()->json([
                'success' => true,
                'data' => $progress
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null
            ]);
        }
    }

    /**
     * Normalize audio metadata from different processing formats
     */
    private function normalizeAudioMetadata(Document $document): array
    {
        $metadata = $document->processing_metadata ?? [];
        
        // Extract duration from different possible sources
        $duration = $this->extractDuration($document, $metadata);
        
        // Extract file sizes
        $mp3Size = $this->extractFileSize($document, $metadata, 'mp3');
        $flacSize = $this->extractFileSize($document, $metadata, 'flac');
        
        // Determine processing status
        $processingStatus = $this->determineProcessingStatus($document, $metadata);

        return [
            'id' => $document->id,
            'title' => $document->title,
            'slug' => $document->slug,
            'type' => $document->type,
            'year' => $document->year,
            'description' => $document->description,
            'cover_url' => $document->cover_image ? route('documents.cover', $document) : null,
            'audio' => [
                'mp3_url' => route('audio.stream', ['document' => $document->id, 'format' => 'mp3']),
                'flac_url' => route('audio.stream', ['document' => $document->id, 'format' => 'flac']),
                'duration' => $duration,
                'duration_formatted' => $this->formatDuration($duration),
                'mp3_size' => $mp3Size,
                'flac_size' => $flacSize,
                'formats_available' => $this->getAvailableFormats($document)
            ],
            'metadata' => [
                'file_type' => $metadata['file_type'] ?? $document->file_mime_type,
                'text_length' => $metadata['text_length'] ?? 0,
                'processed_at' => $metadata['processed_at'] ?? $document->processing_completed_at,
                'processing_method' => $metadata['processed_via'] ?? 'legacy',
                'processing_status' => $processingStatus
            ],
            'can_play' => $this->canPlayAudio($document),
            'play_count' => $document->play_count ?? 0
        ];
    }

    /**
     * Extract duration from various metadata formats
     */
    private function extractDuration(Document $document, array $metadata): float
    {
        // Try different sources for duration
        if (isset($metadata['audio_duration_seconds'])) {
            return floatval($metadata['audio_duration_seconds']);
        }
        
        if (isset($metadata['duration'])) {
            return floatval($metadata['duration']);
        }
        
        if ($document->audio_duration) {
            return floatval($document->audio_duration);
        }

        return 0.0;
    }

    /**
     * Extract file size from metadata
     */
    private function extractFileSize(Document $document, array $metadata, string $format): int
    {
        $sizeKey = $format . '_size';
        
        if (isset($metadata[$sizeKey])) {
            return intval($metadata[$sizeKey]);
        }

        // Fallback to actual content size
        $contentField = $format . '_content';
        $content = $document->$contentField;
        
        return $content ? strlen($content) : 0;
    }

    /**
     * Determine processing status
     */
    private function determineProcessingStatus(Document $document, array $metadata): string
    {
        if (isset($metadata['completion_status'])) {
            return $metadata['completion_status'];
        }

        if (isset($metadata['chunk_status'])) {
            return $metadata['chunk_status'];
        }

        return $document->status ?? 'unknown';
    }

    /**
     * Get available audio formats
     */
    private function getAvailableFormats(Document $document): array
    {
        $formats = [];
        
        if (!empty($document->mp3_content)) {
            $formats[] = 'mp3';
        }
        
        if (!empty($document->flac_content)) {
            $formats[] = 'flac';
        }
        
        return $formats;
    }

    /**
     * Check if audio can be played
     */
    private function canPlayAudio(Document $document): bool
    {
        return $document->status === 'completed' && 
               (!empty($document->mp3_content) || !empty($document->flac_content));
    }

    /**
     * Format duration in MM:SS format
     */
    private function formatDuration(float $seconds): string
    {
        if ($seconds <= 0) {
            return '00:00';
        }

        $minutes = floor($seconds / 60);
        $seconds = floor($seconds % 60);
        
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Check if user can access document
     */
    private function canAccessDocument(Document $document): bool
    {
        // Implement your permission logic here
        // For now, allow if document is active
        return $document->is_active;
    }

    /**
     * Increment play count
     */
    private function incrementPlayCount(Document $document): void
    {
        try {
            // Use atomic increment to avoid race conditions
            $document->increment('play_count');
            
            // Log for analytics
            Log::info('Audio played', [
                'document_id' => $document->id,
                'document_title' => $document->title,
                'user_id' => Auth::id(),
                'ip' => request()->ip()
            ]);
        } catch (\Exception $e) {
            // Don't fail the audio streaming if play count update fails
            Log::warning('Failed to increment play count', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle HTTP range requests for audio streaming
     */
    private function handleRangeRequest($content, $range, $headers): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $size = strlen($content);
        $start = 0;
        $end = $size - 1;

        if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
            $start = intval($matches[1]);
            if (!empty($matches[2])) {
                $end = intval($matches[2]);
            }
        }

        $start = max(0, min($start, $size - 1));
        $end = max($start, min($end, $size - 1));
        $length = $end - $start + 1;

        $headers['Content-Range'] = "bytes $start-$end/$size";
        $headers['Content-Length'] = $length;

        return response()->stream(function() use ($content, $start, $length) {
            echo substr($content, $start, $length);
        }, 206, $headers);
    }
}