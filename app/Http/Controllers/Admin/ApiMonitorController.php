<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ApiMonitorController extends Controller
{
    private const KEYS_PER_PAGE = 10;
    
    public function index(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $page = max(1, $page); // Ensure page is at least 1
        
        // Get API Keys data
        $apiKeysData = $this->getApiKeysData($page);
        
        // Get Queue Statistics
        $queueStats = $this->getQueueStatistics();
        
        // Get Document Statistics  
        $documentStats = $this->getDocumentStatistics();
        
        return view('admin.api-monitor', array_merge($apiKeysData, [
            'queueStats' => $queueStats,
            'documentStats' => $documentStats,
        ]));
    }
    
    private function getApiKeysData($page)
    {
        $rawKeys = config('services.gemini.api_key');
        $allApiKeys = array_filter(array_map('trim', explode(',', $rawKeys)));
        
        $totalKeys = count($allApiKeys);
        $totalPages = max(1, ceil($totalKeys / self::KEYS_PER_PAGE));
        $page = min($page, $totalPages); // Ensure page doesn't exceed total pages
        
        $offset = ($page - 1) * self::KEYS_PER_PAGE;
        $currentPageKeys = array_slice($allApiKeys, $offset, self::KEYS_PER_PAGE);
        
        // Process each key for display
        $processedKeys = [];
        $activeCount = 0;
        $errorCount = 0;
        $untestedCount = 0;
        
        foreach ($currentPageKeys as $localIndex => $key) {
            $globalIndex = $offset + $localIndex;
            $keyData = $this->processApiKey($key, $globalIndex);
            $processedKeys[] = $keyData;
            
            // Count statistics
            if ($keyData['is_active']) {
                $activeCount++;
            } elseif (isset($keyData['last_test'])) {
                $errorCount++;
            } else {
                $untestedCount++;
            }
        }
        
        // Calculate total counts across all pages
        $totalActiveCount = 0;
        $totalErrorCount = 0;
        $totalUntestedCount = 0;
        
        foreach ($allApiKeys as $index => $key) {
            $status = Cache::get("api_key_status_{$index}", 'untested');
            $lastTest = Cache::get("api_key_last_test_{$index}");
            
            if ($status === 'active') {
                $totalActiveCount++;
            } elseif ($lastTest) {
                $totalErrorCount++;
            } else {
                $totalUntestedCount++;
            }
        }
        
        return [
            'apiKeys' => $processedKeys,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalApiKeys' => $totalKeys,
            'currentPageStart' => $offset + 1,
            'currentPageEnd' => min($offset + self::KEYS_PER_PAGE, $totalKeys),
            'activeKeysCount' => $totalActiveCount,
            'errorKeysCount' => $totalErrorCount,
            'untestedKeysCount' => $totalUntestedCount,
        ];
    }
    
    private function processApiKey($key, $index)
    {
        $preview = substr($key, 0, 8) . '***' . substr($key, -4);
        $status = Cache::get("api_key_status_{$index}", 'untested');
        $lastTest = Cache::get("api_key_last_test_{$index}");
        $errorMessage = Cache::get("api_key_error_{$index}");
        
        return [
            'index' => $index,
            'display_index' => $index + 1,
            'preview' => $preview,
            'status' => $status,
            'is_active' => $status === 'active',
            'last_test' => $lastTest ? \Carbon\Carbon::parse($lastTest)->format('M d, H:i') : null,
            'error_message' => $errorMessage,
        ];
    }
    
    private function getQueueStatistics()
    {
        return [
            'pending_jobs' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'jobs_last_hour' => DB::table('jobs')
                ->where('created_at', '>=', now()->subHour())
                ->count(),
            'failed_last_hour' => DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subHour())
                ->count(),
        ];
    }
    
    private function getDocumentStatistics()
    {
        $total = Document::count();
        $completed = Document::where('status', 'completed')->count();
        $processing = Document::where('status', 'processing')->count();
        $failed = Document::where('status', 'failed')->count();
        $today = Document::whereDate('created_at', today())->count();
        
        // Check for stuck documents (processing for more than 2 hours)
        $stuck = Document::where('status', 'processing')
            ->where('processing_started_at', '<', now()->subHours(2))
            ->count();
            
        return [
            'total' => $total,
            'completed' => $completed,
            'processing' => $processing,
            'failed' => $failed,
            'stuck' => $stuck,
            'today' => $today,
        ];
    }
    
    public function testKey(Request $request)
    {
        $request->validate([
            'key_index' => 'required|integer|min:0'
        ]);
        
        $keyIndex = $request->key_index;
        $rawKeys = config('services.gemini.api_key');
        $apiKeys = array_filter(array_map('trim', explode(',', $rawKeys)));
        
        if (!isset($apiKeys[$keyIndex])) {
            return response()->json([
                'status' => 'error',
                'error' => 'API key not found'
            ], 404);
        }
        
        $apiKey = $apiKeys[$keyIndex];
        $result = $this->testSingleApiKey($apiKey, $keyIndex);
        
        return response()->json($result);
    }
    
    public function testAllKeys()
    {
        $rawKeys = config('services.gemini.api_key');
        $apiKeys = array_filter(array_map('trim', explode(',', $rawKeys)));
        
        $results = [
            'total' => count($apiKeys),
            'active' => 0,
            'failed' => 0,
            'details' => []
        ];
        
        foreach ($apiKeys as $index => $key) {
            $result = $this->testSingleApiKey($key, $index);
            $results['details'][] = $result;
            
            if ($result['status'] === 'active') {
                $results['active']++;
            } else {
                $results['failed']++;
            }
            
            // Small delay to avoid rate limiting
            usleep(200000); // 200ms delay
        }
        
        return response()->json($results);
    }
    
    private function testSingleApiKey($apiKey, $index)
    {
        try {
            $url = config('services.gemini.content_url') . '?key=' . $apiKey;
            
            $response = Http::timeout(10)
                ->retry(2, 1000) // Retry 2 times with 1 second delay
                ->post($url, [
                    'contents' => [
                        ['parts' => [['text' => 'Test connection - ' . now()->format('Y-m-d H:i:s')]]]
                    ]
                ]);
            
            $timestamp = now()->format('Y-m-d H:i:s');
            Cache::put("api_key_last_test_{$index}", $timestamp, 3600);
            
            if ($response->successful()) {
                Cache::put("api_key_status_{$index}", 'active', 3600);
                Cache::forget("api_key_error_{$index}");
                
                // Store additional metadata
                Cache::put("api_key_response_time_{$index}", $response->handlerStats()['total_time'] ?? 0, 3600);
                
                return [
                    'status' => 'active',
                    'message' => 'API key is working properly',
                    'response_code' => $response->status(),
                    'response_time' => $response->handlerStats()['total_time'] ?? 0
                ];
            } else {
                $errorMessage = $this->parseApiError($response);
                Cache::put("api_key_status_{$index}", 'error', 3600);
                Cache::put("api_key_error_{$index}", $errorMessage, 3600);
                
                return [
                    'status' => 'error',
                    'error' => $errorMessage,
                    'response_code' => $response->status()
                ];
            }
            
        } catch (\Exception $e) {
            $errorMessage = $this->parseException($e);
            Cache::put("api_key_status_{$index}", 'failed', 3600);
            Cache::put("api_key_error_{$index}", $errorMessage, 3600);
            
            return [
                'status' => 'failed',
                'error' => $errorMessage
            ];
        }
    }
    
    /**
     * Parse API error response to provide meaningful error messages
     */
    private function parseApiError($response)
    {
        $statusCode = $response->status();
        
        switch ($statusCode) {
            case 400:
                return 'Bad Request - Invalid API request format';
            case 401:
                return 'Unauthorized - Invalid API key';
            case 403:
                return 'Forbidden - API key does not have required permissions';
            case 429:
                return 'Rate Limited - Too many requests';
            case 500:
                return 'Server Error - Gemini API is experiencing issues';
            case 503:
                return 'Service Unavailable - Gemini API is temporarily down';
            default:
                return "HTTP {$statusCode} - Unknown error occurred";
        }
    }
    
    /**
     * Parse exception to provide meaningful error messages
     */
    private function parseException(\Exception $e)
    {
        if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
            return 'Connection timeout - Unable to reach Gemini API';
        }
        
        if ($e instanceof \Illuminate\Http\Client\RequestException) {
            return 'Request failed - ' . $e->getMessage();
        }
        
        return 'Network error - ' . $e->getMessage();
    }
    
    public function resetStuckDocuments()
    {
        try {
            $stuckDocuments = Document::where('status', 'processing')
                ->where('processing_started_at', '<', now()->subHours(2))
                ->get();
                
            $resetCount = 0;
            
            foreach ($stuckDocuments as $document) {
                $document->update([
                    'status' => 'pending',
                    'processing_started_at' => null,
                    'processing_metadata' => array_merge($document->processing_metadata ?? [], [
                        'reset_reason' => 'Stuck document reset from API monitor',
                        'reset_at' => now(),
                        'original_started_at' => $document->processing_started_at
                    ])
                ]);
                
                // Optionally re-queue the job
                \App\Jobs\ProcessDocumentJob::dispatch($document);
                $resetCount++;
            }
            
            return response()->json([
                'success' => true,
                'message' => "Successfully reset {$resetCount} stuck documents"
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset stuck documents: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function getQueueStats()
    {
        $stats = $this->getQueueStatistics();
        return response()->json($stats);
    }
    
    public function refreshApiStatus()
    {
        $rawKeys = config('services.gemini.api_key');
        $apiKeys = array_filter(array_map('trim', explode(',', $rawKeys)));
        
        $refreshedCount = 0;
        foreach ($apiKeys as $index => $key) {
            // Force refresh by clearing cache first
            Cache::forget("api_key_status_{$index}");
            Cache::forget("api_key_last_test_{$index}");
            Cache::forget("api_key_error_{$index}");
            
            $this->testSingleApiKey($key, $index);
            $refreshedCount++;
        }
        
        return response()->json([
            'success' => true,
            'message' => "Refreshed status for {$refreshedCount} API keys"
        ]);
    }

    /**
     * Get enhanced API statistics
     */
    public function getApiStatistics()
    {
        $rawKeys = config('services.gemini.api_key');
        $apiKeys = array_filter(array_map('trim', explode(',', $rawKeys)));
        
        $stats = [
            'total_keys' => count($apiKeys),
            'active_keys' => 0,
            'error_keys' => 0,
            'untested_keys' => 0,
            'average_response_time' => 0,
            'last_full_test' => Cache::get('last_full_api_test'),
        ];
        
        $totalResponseTime = 0;
        $testedKeysCount = 0;
        
        foreach ($apiKeys as $index => $key) {
            $status = Cache::get("api_key_status_{$index}", 'untested');
            $responseTime = Cache::get("api_key_response_time_{$index}", 0);
            
            switch ($status) {
                case 'active':
                    $stats['active_keys']++;
                    if ($responseTime > 0) {
                        $totalResponseTime += $responseTime;
                        $testedKeysCount++;
                    }
                    break;
                case 'error':
                case 'failed':
                    $stats['error_keys']++;
                    break;
                default:
                    $stats['untested_keys']++;
            }
        }
        
        if ($testedKeysCount > 0) {
            $stats['average_response_time'] = round($totalResponseTime / $testedKeysCount, 3);
        }
        
        return response()->json($stats);
    }
    
    /**
     * Export API monitor data
     */
    public function exportData()
    {
        $rawKeys = config('services.gemini.api_key');
        $apiKeys = array_filter(array_map('trim', explode(',', $rawKeys)));
        
        $data = [
            'generated_at' => now()->toISOString(),
            'total_keys' => count($apiKeys),
            'keys' => []
        ];
        
        foreach ($apiKeys as $index => $key) {
            $data['keys'][] = [
                'index' => $index + 1,
                'preview' => substr($key, 0, 8) . '***' . substr($key, -4),
                'status' => Cache::get("api_key_status_{$index}", 'untested'),
                'last_test' => Cache::get("api_key_last_test_{$index}"),
                'error_message' => Cache::get("api_key_error_{$index}"),
                'response_time' => Cache::get("api_key_response_time_{$index}", 0)
            ];
        }
        
        return response()->json($data)->header('Content-Disposition', 'attachment; filename="api-monitor-' . now()->format('Y-m-d-H-i') . '.json"');
    }
}