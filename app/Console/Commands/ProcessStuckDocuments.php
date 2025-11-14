<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Jobs\ProcessDocumentJob;

class ProcessStuckDocuments extends Command
{
    protected $signature = 'documents:process-stuck {--force : Force reprocess without confirmation}';
    protected $description = 'Reprocess stuck documents';

    public function handle()
    {
        $stuckDocuments = Document::where('status', 'processing')
            ->where('processing_started_at', '<', now()->subHours(1))
            ->orWhere(function($query) {
                $query->where('status', 'pending')
                      ->where('created_at', '<', now()->subHours(2));
            })
            ->get();

        if ($stuckDocuments->isEmpty()) {
            $this->info('✅ No stuck documents found');
            return;
        }

        $this->info("Found {$stuckDocuments->count()} stuck documents:");
        
        foreach ($stuckDocuments as $doc) {
            $this->line("- ID: {$doc->id} | {$doc->title} | Status: {$doc->status}");
        }

        if (!$this->option('force') && !$this->confirm('Reprocess these documents?')) {
            return;
        }

        foreach ($stuckDocuments as $document) {
            $document->update([
                'status' => 'pending',
                'processing_started_at' => null,
                'processing_metadata' => array_merge($document->processing_metadata ?? [], [
                    'reprocessed_at' => now(),
                    'reprocess_reason' => 'Stuck document command'
                ])
            ]);

            ProcessDocumentJob::dispatch($document);
            $this->info("🔄 Requeued document {$document->id}: {$document->title}");
        }

        $this->info("✅ Requeued {$stuckDocuments->count()} documents");
    }
}
