<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\GeminiApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ApiMonitorController extends Controller
{
    private const KEYS_PER_PAGE = 10;
    private function ensureNotGood($key)
    {
        // sesuaikan dengan field kamu
        $status = $key->is_enabled ? ($key->status ?? 'untested') : 'disabled';
        $isGood = $key->is_enabled && $status === 'active';

        if ($isGood) {
            abort(423, 'Key masih GOOD (Active). Nonaktifkan dulu sebelum edit/hapus.');
        }
    }

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
        $paginator = GeminiApiKey::query()
            ->orderBy('id')
            ->paginate(self::KEYS_PER_PAGE, ['*'], 'page', $page);

        $processedKeys = $paginator->getCollection()->map(function (GeminiApiKey $k) {
            return [
                'id' => $k->id,
                'label' => $k->label,
                'account_email' => $k->account_email,
                'preview' => $k->preview(),
                'status' => $k->status,
                'is_active' => $k->status === 'active',
                'is_enabled' => $k->is_enabled,
                'last_test' => $k->last_test_at?->format('M d, H:i'),
                'error_message' => $k->last_error,
                'response_time' => $k->last_response_time,
                'response_code' => $k->last_response_code,
            ];
        })->all();

        $total = GeminiApiKey::count();
        $active = GeminiApiKey::where('status', 'active')->count();
        $error = GeminiApiKey::whereIn('status', ['error', 'failed'])->count();
        $untested = GeminiApiKey::where('status', 'untested')->count();

        return [
            'apiKeys' => $processedKeys,
            'currentPage' => $paginator->currentPage(),
            'totalPages' => $paginator->lastPage(),
            'totalApiKeys' => $total,
            'currentPageStart' => ($paginator->firstItem() ?? 0),
            'currentPageEnd' => ($paginator->lastItem() ?? 0),

            'activeKeysCount' => $active,
            'errorKeysCount' => $error,
            'untestedKeysCount' => $untested,
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
        $validated = $request->validate([
            'id' => 'required|integer|exists:gemini_api_keys,id',
        ]);

        $key = \App\Models\GeminiApiKey::findOrFail($validated['id']);

        if (!isset($key->is_enabled)) {
            return response()->json([
                'status' => 'error',
                'error' => 'API key not found'
            ], 404);
        }



        $result = $this->testSingleApiKey($key);

        return response()->json($result);
    }

    public function testAllKeys()
    {
        $keys = \App\Models\GeminiApiKey::where('is_enabled', true)->get();

        $results = [
            'total' => $keys->count(),
            'active' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($keys as $key) {
            $r = $this->testSingleApiKey($key);
            $results['details'][] = array_merge(['id' => $key->id], $r);

            if (($r['status'] ?? null) === 'active') $results['active']++;
            else $results['failed']++;

            usleep(200000);
        }

        Cache::put('last_full_api_test', now()->toDateTimeString(), 3600);

        return response()->json($results);
    }


    private function testSingleApiKey(\App\Models\GeminiApiKey $key)
    {
        try {
            $apiKey = (string) $key->api_key; // decrypted via cast

            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

            $response = Http::timeout(10)
                ->retry(2, 1000)
                ->withHeaders([
                    'Content-Type'   => 'application/json',
                    'x-goog-api-key' => $apiKey,
                ])
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => 'Test connection - ' . now()->format('Y-m-d H:i:s')
                                ]
                            ]
                        ]
                    ]
                ]);

            $responseTime = $response->handlerStats()['total_time'] ?? null;

            if ($response->successful()) {
                $key->update([
                    'status' => 'active',
                    'last_test_at' => now(),
                    'last_error' => null,
                    'last_response_code' => $response->status(),
                    'last_response_time' => $responseTime,
                    'tested_count' => $key->tested_count + 1,
                ]);

                return [
                    'status' => 'active',
                    'message' => 'API key is working properly',
                    'response_code' => $response->status(),
                    'response_time' => $responseTime,
                ];
            }

            $errorMessage = $this->parseApiError($response);

            $key->update([
                'status' => 'error',
                'last_test_at' => now(),
                'last_error' => $errorMessage,
                'last_response_code' => $response->status(),
                'last_response_time' => $responseTime,
                'tested_count' => $key->tested_count + 1,
                'failed_count' => $key->failed_count + 1,
            ]);

            return [
                'status' => 'error',
                'error' => $errorMessage,
                'response_code' => $response->status(),
            ];
        } catch (\Throwable $e) {

            $errorMessage = $this->parseException($e);

            $key->update([
                'status' => 'failed',
                'last_test_at' => now(),
                'last_error' => $errorMessage,
                'tested_count' => $key->tested_count + 1,
                'failed_count' => $key->failed_count + 1,
            ]);

            return [
                'status' => 'failed',
                'error' => $errorMessage,
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
        $keys = GeminiApiKey::where('is_enabled', true)->get();

        $refreshedCount = 0;

        foreach ($keys as $key) {

            $this->testSingleApiKey($key);
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
        $total = GeminiApiKey::count();
        $active = GeminiApiKey::where('status', 'active')->count();
        $error = GeminiApiKey::whereIn('status', ['error', 'failed'])->count();
        $untested = GeminiApiKey::where('status', 'untested')->count();

        $avgResponse = GeminiApiKey::whereNotNull('last_response_time')->avg('last_response_time');

        return response()->json([
            'total_keys' => $total,
            'active_keys' => $active,
            'error_keys' => $error,
            'untested_keys' => $untested,
            'average_response_time' => $avgResponse ? round($avgResponse, 3) : 0,
            'last_full_test' => Cache::get('last_full_api_test'),
        ]);
    }


    /**
     * Export API monitor data
     */
    public function exportData()
    {
        $keys = GeminiApiKey::orderBy('id')->get();

        $data = [
            'generated_at' => now()->toISOString(),
            'total_keys' => $keys->count(),
            'keys' => $keys->map(function ($k) {
                return [
                    'id' => $k->id,
                    'label' => $k->label,
                    'account_email' => $k->account_email,
                    'preview' => $k->preview(),
                    'status' => $k->status,
                    'last_test' => $k->last_test_at,
                    'error_message' => $k->last_error,
                    'response_time' => $k->last_response_time,
                    'response_code' => $k->last_response_code,
                ];
            })->all(),
        ];

        return response()
            ->json($data)
            ->header(
                'Content-Disposition',
                'attachment; filename="api-monitor-' . now()->format('Y-m-d-H-i') . '.json"'
            );
    }

    public function storeKey(Request $request)
    {
        // dd('Use the GeminiApiKey model to create keys directly.');
        try {
            //code...
            $data = $request->validate([
                'label' => ['nullable', 'string', 'max:100'],
                'account_email' => ['nullable', 'email', 'max:150'],
                'api_key' => ['required', 'string'], // create wajib ada key
            ]);
            // dd($data);

            GeminiApiKey::create([
                'label' => $data['label'] ?? null,
                'account_email' => $data['account_email'] ?? null,
                'api_key' => $data['api_key'],
                'is_enabled' => true,
                'status' => 'untested',
            ]);

            // return response()->json(['message' => 'Key berhasil ditambahkan']);
            return back()->with('success', 'API key added successfully');
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function updateKey(Request $request, \App\Models\GeminiApiKey $key)
    {
        $this->ensureNotGood($key);

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:120'],
            'account_email' => ['nullable', 'email', 'max:190'],
            'api_key' => ['nullable', 'string', 'min:20'], // adapt
            'is_enabled' => ['required', 'boolean'],
        ]);

        if (!empty($data['api_key'])) {
            $key->api_key = $data['api_key'];
            $key->status = 'untested';
            $key->last_error = null;
            $key->last_test_at = null;
            $key->last_response_code = null;
            $key->last_response_time = null;
            unset($data['api_key']);
        }

        $key->fill($data)->save();

        return back()->with('success', 'API key updated');
    }
    public function destroyKey($id)
    {
        $key = GeminiApiKey::findOrFail($id);
        $this->ensureNotGood($key);

        $key->delete();
        return response()->json(['message' => 'Key terhapus']);
    }
}
