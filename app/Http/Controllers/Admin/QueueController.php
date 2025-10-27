<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class QueueController extends Controller
{
    public function failedJobs()
    {
        $failedJobs = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(50)
            ->get();
            
        return view('admin.failed-jobs', compact('failedJobs'));
    }
    
    public function retryJob($uuid)
    {
        try {
            // Use Artisan command to retry specific job
            Artisan::call('queue:retry', ['id' => $uuid]);
            
            return response()->json([
                'success' => true,
                'message' => 'Job retried successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry job: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function deleteJob($uuid)
    {
        try {
            DB::table('failed_jobs')->where('uuid', $uuid)->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Job deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete job: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function retryAll()
    {
        try {
            // Retry all failed jobs
            Artisan::call('queue:retry', ['id' => 'all']);
            
            return response()->json([
                'success' => true,
                'message' => 'All failed jobs retried successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry all jobs: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function clearAll()
    {
        try {
            // Clear all failed jobs
            DB::table('failed_jobs')->truncate();
            
            return response()->json([
                'success' => true,
                'message' => 'All failed jobs cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear all jobs: ' . $e->getMessage()
            ], 500);
        }
    }
}