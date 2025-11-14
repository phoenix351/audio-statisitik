<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
    public function streamAudio(Request $request, $documentId, $format = 'mp3')
    {
        try {
            $document = Document::findOrFail($documentId);

            // Permissions
            if (!$this->canAccessDocument($document)) {
                abort(403);
            }

            // Validate format
            if (!in_array($format, ['mp3', 'flac'], true)) {
                abort(400, 'Invalid audio format');
            }

            // Resolve file on the 'documents' disk: {id}/audio.{format}
            $disk = Storage::disk('documents');
            $relativePath = "{$document->id}/audio.{$format}";

            if (!$disk->exists($relativePath)) {
                abort(404, 'Audio content not found');
            }

            // Local absolute path (works for local driver)
            if (!method_exists($disk, 'path')) {
                // If you ever switch to a non-local disk, handle via redirect to URL/signed URL here.
                abort(500, 'Audio storage path is not resolvable on this disk');
            }

            $absolutePath = $disk->path($relativePath);
            $mimeType = $format === 'mp3' ? 'audio/mpeg' : 'audio/flac';

            // File size
            $size = @filesize($absolutePath);
            if ($size === false) {
                abort(500, 'Unable to read audio file size');
            }

            // Parse Range header (supports bytes=START-END, bytes=START-, bytes=-SUFFIX_LEN)
            $rangeHeader = $request->headers->get('Range');
            $start = 0;
            $end = $size - 1;
            $status = 200;
            $headers = [
                'Content-Type'  => $mimeType,
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=3600',
            ];

            if ($rangeHeader && preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $m)) {
                $rangeStart = $m[1] === '' ? null : (int) $m[1];
                $rangeEnd   = $m[2] === '' ? null : (int) $m[2];

                if ($rangeStart === null && $rangeEnd === null) {
                    return response('', 416, ['Content-Range' => "bytes */{$size}"]);
                }

                if ($rangeStart === null) {
                    // suffix range: last N bytes
                    $length = min($size, $rangeEnd);
                    $start = $size - $length;
                    $end = $size - 1;
                } elseif ($rangeEnd === null) {
                    // from start to end
                    $start = min($rangeStart, $size - 1);
                    $end = $size - 1;
                } else {
                    $start = min($rangeStart, $size - 1);
                    $end = min($rangeEnd, $size - 1);
                    if ($end < $start) {
                        return response('', 416, ['Content-Range' => "bytes */{$size}"]);
                    }
                }

                $status = 206;
                $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
                $headers['Content-Length'] = (string) ($end - $start + 1);

                Log::info('Audio range request', [
                    'document_id' => $document->id,
                    'format'      => $format,
                    'range'       => "{$start}-{$end}/{$size}",
                ]);
            } else {
                // Full (non-range) request — count as a play
                $this->incrementPlayCount($document);
                $headers['Content-Length'] = (string) $size;
            }

            // Stream the file chunked from $start to $end
            $chunkSize = 1024 * 1024; // 1MB
            return response()->stream(function () use ($absolutePath, $start, $end, $chunkSize) {
                $fp = fopen($absolutePath, 'rb');
                if ($fp === false) {
                    // If we can't open mid-stream, just stop output.
                    return;
                }
                try {
                    fseek($fp, $start);
                    $bytesToOutput = $end - $start + 1;

                    while ($bytesToOutput > 0 && !feof($fp)) {
                        $readLength = ($bytesToOutput > $chunkSize) ? $chunkSize : $bytesToOutput;
                        $buffer = fread($fp, $readLength);
                        if ($buffer === false) {
                            break;
                        }
                        echo $buffer;
                        flush();
                        $bytesToOutput -= strlen($buffer);
                    }
                } finally {
                    fclose($fp);
                }
            }, $status, $headers);
        } catch (\Exception $e) {
            Log::error('Failed to stream audio (file-based)', [
                'document_id' => $documentId,
                'format'      => $format,
                'error'       => $e->getMessage(),
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

            $playlist = $documents->map(function ($document) {
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

        return response()->stream(function () use ($content, $start, $length) {
            echo substr($content, $start, $length);
        }, 206, $headers);
    }
}
