<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class MonitorQueueHealth extends Command
{
    protected $signature = 'queue:health-check {--fix : Automatically fix issues}';
    protected $description = 'Monitor queue health and detect stuck jobs';

    public function handle()
    {
        $this->info('🏥 Checking Queue Health...');
        
        $issues = [];
        
        // Check 1: Stuck jobs
        $stuckJobs = $this->checkStuckJobs();
        if ($stuckJobs > 0) {
            $issues[] = "Found {$stuckJobs} stuck jobs in queue";
        }
        
        // Check 2: Stuck documents
        $stuckDocs = $this->checkStuckDocuments();
        if ($stuckDocs > 0) {
            $issues[] = "Found {$stuckDocs} stuck documents";
        }
        
        // Check 3: Failed jobs
        $failedJobs = DB::table('failed_jobs')->count();
        if ($failedJobs > 10) {
            $issues[] = "High number of failed jobs: {$failedJobs}";
        }
        
        // Check 4: Old pending documents
        $oldPending = Document::where('status', 'pending')
            ->where('created_at', '<', now()->subHours(6))
            ->count();
        if ($oldPending > 0) {
            $issues[] = "Found {$oldPending} old pending documents";
        }
        
        // Check 5: Memory usage issues
        $memoryIssues = $this->checkMemoryIssues();
        if ($memoryIssues) {
            $issues[] = $memoryIssues;
        }
        
        // Display results
        if (empty($issues)) {
            $this->info('✅ Queue health is good');
        } else {
            $this->warn('⚠️  Queue health issues detected:');
            foreach ($issues as $issue) {
                $this->line("  - {$issue}");
            }
            
            if ($this->option('fix')) {
                $this->fixIssues();
            } else {
                $this->info('Run with --fix to automatically resolve issues');
            }
        }
    }
    
    private function checkStuckJobs(): int
    {
        return DB::table('jobs')
            ->where('reserved_at', '<', now()->subMinutes(30)->timestamp)
            ->whereNotNull('reserved_at')
            ->count();
    }
    
    private function checkStuckDocuments(): int
    {
        return Document::where('status', 'processing')
            ->where('processing_started_at', '<', now()->subHours(2))
            ->count();
    }
    
    private function checkMemoryIssues(): ?string
    {
        $memoryLimit = ini_get('memory_limit');
        $currentUsage = memory_get_usage(true);
        $peakUsage = memory_get_peak_usage(true);
        
        // Convert memory limit to bytes
        $limitBytes = $this->convertToBytes($memoryLimit);
        
        if ($peakUsage > $limitBytes * 0.8) {
            return "High memory usage: " . $this->formatBytes($peakUsage) . " / " . $memoryLimit;
        }
        
        return null;
    }
    
    private function fixIssues(): void
    {
        $this->info('🔧 Fixing issues...');
        
        // Fix stuck jobs
        $stuckJobs = DB::table('jobs')
            ->where('reserved_at', '<', now()->subMinutes(30)->timestamp)
            ->whereNotNull('reserved_at')
            ->update(['reserved_at' => null]);
        
        if ($stuckJobs > 0) {
            $this->info("✅ Released {$stuckJobs} stuck jobs");
        }
        
        // Fix stuck documents
        $stuckDocs = Document::where('status', 'processing')
            ->where('processing_started_at', '<', now()->subHours(2))
            ->update([
                'status' => 'pending',
                'processing_started_at' => null
            ]);
        
        if ($stuckDocs > 0) {
            $this->info("✅ Reset {$stuckDocs} stuck documents");
        }
        
        // Clear old cache
        Cache::flush();
        $this->info("✅ Cleared cache");
    }
    
    private function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value)-1]);
        $value = (int) $value;
        
        switch($last) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        
        return $value;
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}