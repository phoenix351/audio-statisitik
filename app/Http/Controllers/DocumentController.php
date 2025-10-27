<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Indicator;
use App\Models\VisitorLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\HomeController;

class DocumentController extends Controller
{
    public function publications(Request $request)
    {
        VisitorLog::logVisit('publications');

        $originalQuery = $request->input('query') ?? $request->input('search');

        // parsing query
        $parsed = app(\App\Http\Controllers\HomeController::class)->parseSearchInput($originalQuery);

        $type = 'publication';
        $year = $request->input('year') ?? $parsed['tahun'];
        $indicator = $request->input('indicator') ?? $parsed['indikator'];
        $searchText = trim($parsed['sisa']);

        $documents = Document::with('indicator')
            ->active()
            ->completed()
            ->where('type', $type)
            ->when($searchText, function ($q) use ($searchText) {
                $terms = preg_split('/\s+/', $searchText);
                $q->where(function ($query) use ($terms) {
                    foreach ($terms as $term) {
                        $query->where(function ($sub) use ($term) {
                            $sub->where('title', 'LIKE', "%{$term}%")
                                ->orWhere('description', 'LIKE', "%{$term}%")
                                ->orWhereHas('indicator', function ($q2) use ($term) {
                                    $q2->where('name', 'LIKE', "%{$term}%");
                                });
                        });
                    }
                });
            })
            ->when($year, fn($q) => $q->where('year', $year))
            ->when($indicator, fn($q) => $q->where('indicator_id', $indicator))
            ->paginate(12);

        $indicators = Indicator::where('is_active', true)->get();
        $years = Document::active()->where('type', $type)->distinct()->pluck('year')->sort()->values();

        // 🔹 Voice auto (selalu aktif pas buka halaman)
        $count = $documents->total();
        $voiceMessage = "Anda sedang berada di halaman Publikasi. ";

        if ($count === 0) {
            $voiceMessage .= "Tidak ditemukan dokumen Publikasi. Silakan gunakan filter tahun atau indikator.";
        } elseif ($count <= 5) {
            $voiceMessage .= "Ditemukan {$count} dokumen. ";
            foreach ($documents as $i => $doc) {
                $voiceMessage .= "Nomor " . ($i + 1) . ": {$doc->title}. ";
            }
            $voiceMessage .= "Silakan katakan 'pilih dokumen nomor' untuk membuka, atau 'putar dokumen nomor' untuk mendengarkan audio.";
        } else {
            $voiceMessage .= "Ditemukan {$count} dokumen. Silakan gunakan filter untuk mempersempit pencarian. ";
            if (!$year) $voiceMessage .= "Anda bisa katakan 'filter tahun 2023'. ";
            if (!$indicator) $voiceMessage .= "Atau katakan nama indikator seperti 'filter inflasi'.";
        }

        return view('documents.publications', [
            'documents'   => $documents,
            'indicators'  => $indicators,
            'years'       => $years,
            'query'       => $searchText,
            'originalQuery' => $originalQuery,
            'year'        => $year,
            'indicator'   => $indicator,
            'voiceMessage' => $voiceMessage,
        ]);
    }

    public function brs(Request $request)
    {
        VisitorLog::logVisit('brs');

        $originalQuery = $request->input('query') ?? $request->input('search');

        // parsing query
        $parsed = app(\App\Http\Controllers\HomeController::class)->parseSearchInput($originalQuery);

        $type = 'brs';
        $year = $request->input('year') ?? $parsed['tahun'];
        $indicator = $request->input('indicator') ?? $parsed['indikator'];
        $searchText = trim($parsed['sisa']);

        $documents = Document::with('indicator')
            ->active()
            ->completed()
            ->where('type', $type)
            ->when($searchText, function ($q) use ($searchText) {
                $terms = preg_split('/\s+/', $searchText);
                $q->where(function ($query) use ($terms) {
                    foreach ($terms as $term) {
                        $query->where(function ($sub) use ($term) {
                            $sub->where('title', 'LIKE', "%{$term}%")
                                ->orWhere('description', 'LIKE', "%{$term}%")
                                ->orWhereHas('indicator', function ($q2) use ($term) {
                                    $q2->where('name', 'LIKE', "%{$term}%");
                                });
                        });
                    }
                });
            })
            ->when($year, fn($q) => $q->where('year', $year))
            ->when($indicator, fn($q) => $q->where('indicator_id', $indicator))
            ->paginate(12);

        $indicators = Indicator::where('is_active', true)->get();
        $years = Document::active()->where('type', $type)->distinct()->pluck('year')->sort()->values();

        // 🔹 Voice auto (selalu aktif pas buka halaman)
        $count = $documents->total();
        $voiceMessage = "Anda sedang berada di halaman Berita Resmi Statistik. ";

        if ($count === 0) {
            $voiceMessage .= "Tidak ditemukan dokumen BRS. Silakan gunakan filter tahun atau indikator.";
        } elseif ($count <= 5) {
            $voiceMessage .= "Ditemukan {$count} dokumen. ";
            foreach ($documents as $i => $doc) {
                $voiceMessage .= "Nomor " . ($i + 1) . ": {$doc->title}. ";
            }
            $voiceMessage .= "Silakan katakan 'pilih dokumen nomor' untuk membuka, atau 'putar dokumen nomor' untuk mendengarkan audio.";
        } else {
            $voiceMessage .= "Ditemukan {$count} dokumen. Silakan gunakan filter untuk mempersempit pencarian. ";
            if (!$year) $voiceMessage .= "Anda bisa katakan 'filter tahun 2023'. ";
            if (!$indicator) $voiceMessage .= "Atau katakan nama indikator seperti 'filter inflasi'.";
        }

        return view('documents.brs', [
            'documents'   => $documents,
            'indicators'  => $indicators,
            'years'       => $years,
            'query'       => $searchText,
            'originalQuery' => $originalQuery,
            'year'        => $year,
            'indicator'   => $indicator,
            'voiceMessage' => $voiceMessage,
        ]);
    }

    public function show(Document $document)
    {
        try {
            // Increment view count
            $document->increment('view_count');
            
            // Prepare audio metadata for the view
            $audioMetadata = $this->prepareAudioMetadata($document);
            
            // Get related documents
            $relatedDocuments = $this->getRelatedDocuments($document);
            
            return view('documents.show', compact('document', 'audioMetadata', 'relatedDocuments'));
            
        } catch (\Exception $e) {
            Log::error('Failed to show document', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('documents.index')
                           ->with('error', 'Document not found or could not be loaded');
        }
    }

    public function cover(Document $document)
    {
        try {
            if (empty($document->cover_image)) {
                abort(404, 'Cover image not found');
            }

            $mimeType = $document->cover_mime_type ?: 'image/jpeg';
            
            return response($document->cover_image)
                ->header('Content-Type', $mimeType)
                ->header('Cache-Control', 'public, max-age=86400'); // Cache for 24 hours
                
        } catch (\Exception $e) {
            Log::error('Failed to serve cover image', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
            abort(404);
        }
    }

    public function getCover(Document $document)
    {
        if ($document->cover_image && $document->cover_mime_type) {
            return response($document->cover_image, 200, [
                'Content-Type' => $document->cover_mime_type,
                'Cache-Control' => 'public, max-age=3600',
                'Content-Length' => strlen($document->cover_image),
            ]);
        }

        $defaultCoverData = $this->generateDefaultCover($document);

        return response($defaultCoverData, 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function download(Document $document)
    {
        try {
            if (empty($document->file_content)) {
                abort(404, 'File content not found');
            }

            // Increment download count
            $document->increment('download_count');
            
            // Log download
            Log::info('Document downloaded', [
                'document_id' => $document->id,
                'document_title' => $document->title,
                'user_id' => auth()->id(),
                'ip' => request()->ip()
            ]);

            return response($document->file_content)
                ->header('Content-Type', $document->file_mime_type ?: 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename="' . $document->file_name . '"')
                ->header('Content-Length', strlen($document->file_content));
                
        } catch (\Exception $e) {
            Log::error('Failed to download document', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
            abort(500);
        }
    }

     public function downloadAudio(Document $document, $format)
    {
        if (!$document->is_active || $document->status !== 'completed') {
            abort(404, 'Document not available');
        }

        if (!in_array($format, ['mp3', 'flac'])) {
            abort(400, 'Invalid audio format');
        }

        // Get audio content based on format
        $audioContent = null;
        if ($format === 'mp3' && $document->mp3_content) {
            $audioContent = $document->mp3_content;
        } elseif ($format === 'flac' && $document->flac_content) {
            $audioContent = $document->flac_content;
        }

        if (!$audioContent) {
            abort(404, 'Audio file not found');
        }

        // Log download
        VisitorLog::logVisit("document/{$document->slug}", 'download', $document->id, [
            'format' => $format
        ]);

        $document->increment('download_count');

        $mimeType = $format === 'mp3' ? 'audio/mpeg' : 'audio/flac';
        $fileName = "{$document->slug}.{$format}";

        return response($audioContent, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Length' => strlen($audioContent),
            'Accept-Ranges' => 'bytes',
        ]);
    }
    
     /**
     * Get documents with audio capability for listing
     */
    public function indexWithAudio(Request $request): JsonResponse
    {
        try {
            $query = Document::where('is_active', true)
                           ->where('status', 'completed')
                           ->whereNotNull('mp3_content');

            // Apply filters
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->filled('year')) {
                $query->where('year', $request->year);
            }

            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('title', 'like', "%{$searchTerm}%")
                      ->orWhere('description', 'like', "%{$searchTerm}%")
                      ->orWhere('extracted_text', 'like', "%{$searchTerm}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            if (in_array($sortBy, ['title', 'year', 'play_count', 'created_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = min($request->get('per_page', 12), 50);
            $documents = $query->paginate($perPage);

            // Transform documents with audio metadata
            $documents->getCollection()->transform(function ($document) {
                return $this->transformDocumentForAudio($document);
            });

            return response()->json([
                'success' => true,
                'data' => $documents
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get documents with audio', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load documents'
            ], 500);
        }
    }

    /**
     * Get audio-ready documents for playlist generation
     */
    public function getAudioPlaylist(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'document_ids' => 'array',
                'document_ids.*' => 'integer|exists:documents,id',
                'type' => 'string|nullable',
                'year' => 'integer|nullable',
                'limit' => 'integer|min:1|max:100'
            ]);

            $query = Document::where('is_active', true)
                           ->where('status', 'completed')
                           ->whereNotNull('mp3_content');

            // Filter by specific document IDs if provided
            if ($request->filled('document_ids')) {
                $query->whereIn('id', $request->document_ids);
            } else {
                // Apply other filters
                if ($request->filled('type')) {
                    $query->where('type', $request->type);
                }

                if ($request->filled('year')) {
                    $query->where('year', $request->year);
                }

                // Limit results
                $limit = $request->get('limit', 20);
                $query->limit($limit);
            }

            $documents = $query->orderBy('created_at', 'desc')->get();

            $playlist = $documents->map(function ($document) {
                return $this->transformDocumentForAudio($document);
            });

            return response()->json([
                'success' => true,
                'data' => $playlist,
                'total' => $playlist->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate audio playlist', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate playlist'
            ], 500);
        }
    }

    /**
     * Prepare audio metadata for a document
     */
    private function prepareAudioMetadata(Document $document): array
    {
        $metadata = $document->processing_metadata ?? [];
        
        // Normalize metadata from different processing formats
        return [
            'can_play' => $this->canPlayAudio($document),
            'has_mp3' => !empty($document->mp3_content),
            'has_flac' => !empty($document->flac_content),
            'duration' => $this->extractDuration($document, $metadata),
            'duration_formatted' => $this->formatDuration($this->extractDuration($document, $metadata)),
            'file_sizes' => [
                'mp3' => $this->getContentSize($document->mp3_content),
                'flac' => $this->getContentSize($document->flac_content)
            ],
            'processing_info' => [
                'method' => $metadata['processed_via'] ?? 'legacy',
                'status' => $this->determineProcessingStatus($document, $metadata),
                'processed_at' => $metadata['processed_at'] ?? $document->processing_completed_at,
                'text_length' => $metadata['text_length'] ?? 0
            ],
            'play_count' => $document->play_count ?? 0,
            'audio_urls' => [
                'mp3' => $this->canPlayAudio($document) ? route('audio.stream', ['document' => $document->id, 'format' => 'mp3']) : null,
                'flac' => $this->canPlayAudio($document) && !empty($document->flac_content) ? route('audio.stream', ['document' => $document->id, 'format' => 'flac']) : null
            ]
        ];
    }

    /**
     * Transform document for audio player
     */
    private function transformDocumentForAudio(Document $document): array
    {
        $metadata = $document->processing_metadata ?? [];
        
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
                'flac_url' => !empty($document->flac_content) ? route('audio.stream', ['document' => $document->id, 'format' => 'flac']) : null,
                'duration' => $this->extractDuration($document, $metadata),
                'duration_formatted' => $this->formatDuration($this->extractDuration($document, $metadata)),
                'mp3_size' => $this->getContentSize($document->mp3_content),
                'flac_size' => $this->getContentSize($document->flac_content),
                'formats_available' => $this->getAvailableFormats($document)
            ],
            'metadata' => [
                'file_type' => $metadata['file_type'] ?? $document->file_mime_type,
                'text_length' => $metadata['text_length'] ?? 0,
                'processed_at' => $metadata['processed_at'] ?? $document->processing_completed_at,
                'processing_method' => $metadata['processed_via'] ?? 'legacy',
                'processing_status' => $this->determineProcessingStatus($document, $metadata)
            ],
            'stats' => [
                'play_count' => $document->play_count ?? 0,
                'download_count' => $document->download_count ?? 0
            ],
            'can_play' => $this->canPlayAudio($document)
        ];
    }

    /**
     * Get related documents for recommendations
     */
    private function getRelatedDocuments(Document $document, int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return Document::where('is_active', true)
                      ->where('status', 'completed')
                      ->where('id', '!=', $document->id)
                      ->where(function($query) use ($document) {
                          $query->where('type', $document->type)
                                ->orWhere('year', $document->year);
                      })
                      ->whereNotNull('mp3_content')
                      ->orderBy('play_count', 'desc')
                      ->limit($limit)
                      ->get();
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
     * Get content size safely
     */
    private function getContentSize(?string $content): int
    {
        return $content ? strlen($content) : 0;
    }

    /**
     * Determine processing status from metadata
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
     * Get available audio formats for a document
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
     * Update document metadata for legacy documents (migration helper)
     */
    public function updateLegacyMetadata(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'document_id' => 'required|integer|exists:documents,id'
            ]);

            $document = Document::findOrFail($request->document_id);
            
            // Check if document has legacy metadata format
            $metadata = $document->processing_metadata ?? [];
            
            if (!isset($metadata['processed_via']) && $this->canPlayAudio($document)) {
                // This is likely a legacy document, update its metadata
                $updatedMetadata = array_merge($metadata, [
                    'processed_via' => 'legacy',
                    'completion_status' => 'success',
                    'mp3_size' => $this->getContentSize($document->mp3_content),
                    'flac_size' => $this->getContentSize($document->flac_content),
                    'audio_duration_seconds' => $document->audio_duration ?: 0,
                    'migrated_at' => now(),
                    'migration_version' => '1.0'
                ]);

                $document->update([
                    'processing_metadata' => $updatedMetadata
                ]);

                Log::info('Legacy document metadata updated', [
                    'document_id' => $document->id,
                    'title' => $document->title
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Document metadata updated successfully',
                    'data' => $this->transformDocumentForAudio($document)
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Document metadata is already up to date',
                'data' => $this->transformDocumentForAudio($document)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update legacy metadata', [
                'document_id' => $request->document_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update document metadata'
            ], 500);
        }
    }

    /**
     * Batch update legacy documents metadata
     */
    public function batchUpdateLegacyMetadata(): JsonResponse
    {
        try {
            // Find documents with legacy metadata format
            $legacyDocuments = Document::where('status', 'completed')
                                     ->whereNotNull('mp3_content')
                                     ->whereJsonDoesntContain('processing_metadata->processed_via', 'queue')
                                     ->get();

            $updated = 0;
            $errors = 0;

            foreach ($legacyDocuments as $document) {
                try {
                    $metadata = $document->processing_metadata ?? [];
                    
                    $updatedMetadata = array_merge($metadata, [
                        'processed_via' => 'legacy',
                        'completion_status' => 'success',
                        'mp3_size' => $this->getContentSize($document->mp3_content),
                        'flac_size' => $this->getContentSize($document->flac_content),
                        'audio_duration_seconds' => $document->audio_duration ?: 0,
                        'migrated_at' => now(),
                        'migration_version' => '1.0'
                    ]);

                    $document->update([
                        'processing_metadata' => $updatedMetadata
                    ]);

                    $updated++;

                } catch (\Exception $e) {
                    Log::warning('Failed to update document metadata', [
                        'document_id' => $document->id,
                        'error' => $e->getMessage()
                    ]);
                    $errors++;
                }
            }

            Log::info('Batch metadata update completed', [
                'total_found' => $legacyDocuments->count(),
                'updated' => $updated,
                'errors' => $errors
            ]);

            return response()->json([
                'success' => true,
                'message' => "Metadata update completed",
                'data' => [
                    'total_found' => $legacyDocuments->count(),
                    'updated' => $updated,
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed batch metadata update', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform batch metadata update'
            ], 500);
        }
    }

    public function streamAudio(Document $document, $format)
    {
        if (!$document->is_active || $document->status !== 'completed') {
            Log::warning("Document not available for streaming", [
                'document_id' => $document->id,
                'status' => $document->status,
                'is_active' => $document->is_active
            ]);
            abort(404, 'Document not available');
        }

        if (!in_array($format, ['mp3', 'flac'])) {
            abort(400, 'Invalid audio format');
        }

        // Get audio content based on format
        $audioContent = null;
        $audioField = $format === 'mp3' ? 'mp3_content' : 'flac_content';
        
        if ($document->$audioField) {
            try {
                $audioContent = base64_decode($document->$audioField);
                
                Log::info("Audio streaming", [
                    'document_id' => $document->id,
                    'format' => $format,
                    'content_length' => strlen($audioContent)
                ]);
                
            } catch (\Exception $e) {
                Log::error("Failed to decode audio content", [
                    'document_id' => $document->id,
                    'format' => $format,
                    'error' => $e->getMessage()
                ]);
                abort(500, 'Audio content corrupted');
            }
        }

        if (!$audioContent || strlen($audioContent) === 0) {
            Log::warning("Audio content not found or empty", [
                'document_id' => $document->id,
                'format' => $format,
                'has_mp3' => !empty($document->mp3_content),
                'has_flac' => !empty($document->flac_content)
            ]);
            abort(404, 'Audio file not found');
        }

        $mimeType = $format === 'mp3' ? 'audio/mpeg' : 'audio/flac';
        $size = strlen($audioContent);
        $start = 0;
        $end = $size - 1;

        // Handle Range requests for seeking
        $headers = [
            'Content-Type' => $mimeType,
            'Accept-Ranges' => 'bytes',
            'Content-Length' => $size,
            'Cache-Control' => 'public, max-age=3600',
        ];

        // Check for Range header
        $range = request()->header('Range');
        if ($range) {
            if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
                $start = intval($matches[1]);
                if (!empty($matches[2])) {
                    $end = intval($matches[2]);
                }
                
                $length = $end - $start + 1;
                
                $headers['Content-Length'] = $length;
                $headers['Content-Range'] = "bytes $start-$end/$size";
                
                Log::info("Range request", [
                    'document_id' => $document->id,
                    'format' => $format,
                    'range' => "$start-$end/$size"
                ]);

                return response(substr($audioContent, $start, $length), 206, $headers);
            }
        }

        // Increment play count for full streams
        $document->increment('play_count');

        return response($audioContent, 200, $headers);
    }

    public function checkStatus(Document $document)
    {
        return response()->json([
            'status' => $document->status,
            'has_audio' => $document->hasAudio(),
            'audio_duration' => $document->getAudioDurationFormatted(),
            'audio_duration_seconds' => $document->audio_duration,
            'has_mp3' => !empty($document->mp3_content),
            'has_flac' => !empty($document->flac_content),
            'processing_metadata' => $document->processing_metadata,
            'error_message' => $document->processing_metadata['error'] ?? null,
        ]);
    }

    private function generateDefaultCover(Document $document): string
    {
        try {
            $image = imagecreate(300, 400);
            
            $bg = imagecolorallocate($image, 240, 248, 255);
            $text = imagecolorallocate($image, 30, 64, 175);
            $border = imagecolorallocate($image, 59, 130, 246);
            $accent = $document->type === 'publication' ? 
                imagecolorallocate($image, 59, 130, 246) : 
                imagecolorallocate($image, 34, 197, 94);
            
            imagefill($image, 0, 0, $bg);
            imagerectangle($image, 0, 0, 299, 399, $border);
            imagerectangle($image, 5, 5, 294, 394, $border);
            
            $badgeY = 30;
            $badgeText = $document->type === 'publication' ? 'PUBLIKASI' : 'BRS';
            imagefilledrectangle($image, 20, $badgeY, 280, $badgeY + 25, $accent);
            imagestring($image, 3, 100, $badgeY + 8, $badgeText, $bg);
            
            $iconY = 120;
            $iconX = 120;
            imagefilledrectangle($image, $iconX, $iconY, $iconX + 60, $iconY + 60, $text);
            imagefilledrectangle($image, $iconX + 10, $iconY + 10, $iconX + 50, $iconY + 50, $bg);
            
            for ($i = 0; $i < 4; $i++) {
                $lineY = $iconY + 15 + ($i * 8);
                imageline($image, $iconX + 15, $lineY, $iconX + 45, $lineY, $text);
            }
            
            imagestring($image, 4, 130, 200, $document->year, $text);
            
            if ($document->indicator) {
                $indicator = substr($document->indicator->name, 0, 15);
                imagestring($image, 2, 60, 230, $indicator, $text);
            }
            
            imagestring($image, 3, 130, 300, 'BPS', $text);
            imagestring($image, 2, 95, 320, 'SULAWESI UTARA', $text);
            
            ob_start();
            imagejpeg($image, null, 85);
            $imageData = ob_get_contents();
            ob_end_clean();
            
            imagedestroy($image);
            return $imageData;
            
        } catch (\Exception $e) {
            Log::error("Failed to generate default cover: " . $e->getMessage());
            $image = imagecreate(1, 1);
            $color = imagecolorallocate($image, 240, 248, 255);
            ob_start();
            imagejpeg($image);
            $data = ob_get_contents();
            ob_end_clean();
            imagedestroy($image);
            return $data;
        }
    }
}