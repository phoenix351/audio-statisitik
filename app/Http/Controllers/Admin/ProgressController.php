<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use App\Models\Document;

class ProgressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Get progress for a specific document
     */
    public function getDocumentProgress(Document $document)
    {
        $progressKey = "document_progress_{$document->id}";
        $progress = Cache::get($progressKey);
        
        if (!$progress) {
            // If no progress data, check document status
            return response()->json([
                'status' => $document->status,
                'percentage' => $document->status === 'completed' ? 100 : 0,
                'message' => $this->getStatusMessage($document->status),
                'document_id' => $document->id,
                'document_title' => $document->title,
                'found' => false
            ]);
        }

        $progress['found'] = true;
        return response()->json($progress);
    }

    /**
     * Get progress for all processing documents
     */
    public function getAllProgress()
    {
        $processingDocuments = Document::whereIn('status', ['pending', 'processing'])
            ->orderBy('created_at', 'desc')
            ->get();

        $allProgress = [];

        foreach ($processingDocuments as $document) {
            $progressKey = "document_progress_{$document->id}";
            $progress = Cache::get($progressKey);
            
            if ($progress) {
                $allProgress[] = $progress;
            } else {
                $allProgress[] = [
                    'document_id' => $document->id,
                    'document_title' => $document->title,
                    'status' => $document->status,
                    'percentage' => $document->status === 'pending' ? 0 : 10,
                    'message' => $this->getStatusMessage($document->status),
                    'updated_at' => $document->updated_at->toISOString(),
                    'found' => false
                ];
            }
        }

        return response()->json([
            'progress_items' => $allProgress,
            'total_processing' => count($allProgress)
        ]);
    }

    /**
     * Get processing statistics
     */
    public function getStats()
    {
        $stats = [
            'total_documents' => Document::count(),
            'completed' => Document::where('status', 'completed')->count(),
            'processing' => Document::where('status', 'processing')->count(),
            'pending' => Document::where('status', 'pending')->count(),
            'failed' => Document::where('status', 'failed')->count(),
            'with_audio' => Document::whereNotNull('mp3_content')->count(),
        ];

        return response()->json($stats);
    }

    private function getStatusMessage($status)
    {
        return match($status) {
            'pending' => 'Menunggu untuk diproses...',
            'processing' => 'Sedang diproses...',
            'completed' => 'Proses selesai',
            'failed' => 'Proses gagal',
            default => 'Status tidak dikenal'
        };
    }
}