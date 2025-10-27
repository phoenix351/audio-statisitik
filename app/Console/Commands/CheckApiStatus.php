<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class CheckApiStatus extends Command
{
    protected $signature = 'api:check-status';
    protected $description = 'Check API keys status and quota';

    public function handle()
    {
        $this->info('🔍 Checking API Status...');
        
        // Check Gemini API Keys
        $this->checkGeminiApiKeys();
        
        // Check Queue Status
        $this->checkQueueStatus();
        
        // Check Stuck Documents
        $this->checkStuckDocuments();
        
        $this->info('✅ API Status check completed');
    }
    
    private function checkGeminiApiKeys()
    {
        $this->info('📊 Checking Gemini API Keys...');
        
        $rawKeys = config('services.gemini.api_key');
        $apiKeys = array_filter(array_map('trim', explode(',', $rawKeys)));
        
        $this->table(['Index', 'Key Preview', 'Status', 'Last Test'], 
            collect($apiKeys)->map(function($key, $index) {
                $preview = substr($key, 0, 8) . '***' . substr($key, -4);
                $status = $this->testApiKey($key, $index);
                $lastTest = Cache::get("api_key_last_test_{$index}", 'Never');
                
                return [$index, $preview, $status['status'], $lastTest];
            })->toArray()
        );
    }
    
    private function testApiKey($apiKey, $index)
    {
        try {
            $url = config('services.gemini.content_url') . '?key=' . $apiKey;
            
            $response = Http::timeout(10)->post($url, [
                'contents' => [
                    ['parts' => [['text' => 'Test']]]
                ]
            ]);
            
            Cache::put("api_key_last_test_{$index}", now()->format('Y-m-d H:i:s'), 3600);
            
            if ($response->successful()) {
                Cache::put("api_key_status_{$index}", 'active', 3600);
                return ['status' => '✅ Active', 'response' => $response->status()];
            } else {
                Cache::put("api_key_status_{$index}", 'error', 3600);
                return ['status' => '❌ Error: ' . $response->status(), 'response' => $response->status()];
            }
            
        } catch (\Exception $e) {
            Cache::put("api_key_status_{$index}", 'failed', 3600);
            return ['status' => '🔥 Failed: ' . $e->getMessage(), 'response' => 0];
        }
    }
    
    private function checkQueueStatus()
    {
        $this->info('⚡ Checking Queue Status...');
        
        // Check if queue worker is running (simplified check)
        $queueStats = [
            'pending_jobs' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'processing_docs' => Document::where('status', 'processing')->count(),
            'pending_docs' => Document::where('status', 'pending')->count(),
        ];
        
        $this->table(['Metric', 'Count'], [
            ['Pending Jobs', $queueStats['pending_jobs']],
            ['Failed Jobs', $queueStats['failed_jobs']],
            ['Processing Documents', $queueStats['processing_docs']],
            ['Pending Documents', $queueStats['pending_docs']],
        ]);
        
        if ($queueStats['pending_jobs'] > 10) {
            $this->warn('⚠️  High number of pending jobs. Consider running more workers.');
        }
        
        if ($queueStats['failed_jobs'] > 5) {
            $this->error('🚨 High number of failed jobs. Check failed_jobs table for errors.');
        }
    }
    
    private function checkStuckDocuments()
    {
        $this->info('🔍 Checking for stuck documents...');
        
        $stuckDocs = Document::where('status', 'processing')
            ->where('processing_started_at', '<', now()->subHours(2))
            ->get();
            
        if ($stuckDocs->count() > 0) {
            $this->warn("Found {$stuckDocs->count()} stuck documents:");
            
            $this->table(['ID', 'Title', 'Started', 'Duration'], 
                $stuckDocs->map(function($doc) {
                    return [
                        $doc->id,
                        Str::limit($doc->title, 30),
                        $doc->processing_started_at->format('Y-m-d H:i:s'),
                        $doc->processing_started_at->diffForHumans()
                    ];
                })->toArray()
            );
            
            if ($this->confirm('Reset stuck documents to pending status?')) {
                foreach ($stuckDocs as $doc) {
                    $doc->update([
                        'status' => 'pending',
                        'processing_started_at' => null,
                        'processing_metadata' => array_merge($doc->processing_metadata ?? [], [
                            'reset_reason' => 'Stuck document reset by command',
                            'reset_at' => now(),
                            'original_started_at' => $doc->processing_started_at
                        ])
                    ]);
                }
                $this->info("✅ Reset {$stuckDocs->count()} stuck documents");
            }
        } else {
            $this->info('✅ No stuck documents found');
        }
    }
}