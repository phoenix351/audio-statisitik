@extends('layouts.app')

@section('title', 'API Monitor - Admin')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center space-x-2 text-sm text-gray-500 mb-2">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-blue-600">Dashboard</a>
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
                <span>API Monitor</span>
            </div>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">API Monitor</h1>
                    <p class="text-gray-600 mt-2">Monitor API keys dan queue status</p>
                </div>
                <div class="flex space-x-3">
                    <button data-action="refreshAllData"
                        class=" cursor-pointer inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        <i class="fas fa-sync mr-2" aria-hidden="true"></i>
                        Refresh
                    </button>
                    <button data-action="testAllKeys"
                        class="cursor-pointer inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                        <i class="fas fa-vial mr-2" aria-hidden="true"></i>
                        Test All Keys
                    </button>
                </div>
            </div>
        </div>

        <!-- API Keys Status with Pagination -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-semibold text-gray-900">Gemini API Keys</h3>
                    <p class="text-sm text-gray-600 mt-1">Total: {{ $totalApiKeys }} keys | Showing
                        {{ $currentPageStart }}-{{ $currentPageEnd }}</p>
                </div>

                <!-- Pagination Info -->
                @if ($totalPages > 1)
                    <div class="flex items-center space-x-2">
                        <button data-action="changePage" data-page="{{ $currentPage - 1 }}"
                            class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50 {{ $currentPage <= 1 ? 'opacity-50 cursor-not-allowed' : '' }}"
                            {{ $currentPage <= 1 ? 'disabled' : '' }}>
                            <i class="fas fa-chevron-left" aria-hidden="true"></i>
                        </button>
                        <span class="text-sm text-gray-600">
                            Page {{ $currentPage }} of {{ $totalPages }}
                        </span>
                        <button data-action="changePage" data-page="{{ $currentPage + 1 }}"
                            class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50 {{ $currentPage >= $totalPages ? 'opacity-50 cursor-not-allowed' : '' }}"
                            {{ $currentPage >= $totalPages ? 'disabled' : '' }}>
                            <i class="fas fa-chevron-right" aria-hidden="true"></i>
                        </button>
                    </div>
                @endif
            </div>

            <!-- API Keys Grid - 2 Columns -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Column 1 -->
                <div class="space-y-4">
                    <h4 class="text-sm font-medium text-gray-700 border-b border-gray-200 pb-2">
                        <i class="fas fa-key mr-2 text-blue-500" aria-hidden="true"></i>
                        Keys {{ $currentPageStart }}-{{ min($currentPageStart + 4, $currentPageEnd) }}
                    </h4>
                    @foreach (array_slice($apiKeys, 0, 5) as $index => $api)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center space-x-3">
                                    <div
                                        class="w-4 h-4 rounded-full {{ $api['is_active'] ? 'bg-green-500' : 'bg-red-500' }}">
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900">API Key #{{ $api['display_index'] }}</div>
                                        <div class="text-sm text-gray-500 font-mono">{{ $api['preview'] }}</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $api['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $api['is_active'] ? 'Active' : 'Error' }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-sm">
                                <div class="text-gray-600">
                                    <i class="fas fa-clock mr-1" aria-hidden="true"></i>
                                    Last Test: <span class="font-medium">{{ $api['last_test'] ?: 'Never' }}</span>
                                </div>
                                <button class="js-test-api cursor-pointer" data-key-index="{{ $api['index'] }}"
                                    class="inline-flex items-center px-3 py-1 border border-blue-300 text-blue-600 hover:bg-blue-50 rounded-md text-xs font-medium transition-colors">
                                    <i class="fas fa-play mr-1" aria-hidden="true"></i>
                                    Test
                                </button>
                            </div>

                            @if (!$api['is_active'] && isset($api['error_message']))
                                <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-700">
                                    <i class="fas fa-exclamation-triangle mr-1" aria-hidden="true"></i>
                                    {{ $api['error_message'] }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Column 2 -->
                <div class="space-y-4">
                    <h4 class="text-sm font-medium text-gray-700 border-b border-gray-200 pb-2">
                        <i class="fas fa-key mr-2 text-blue-500" aria-hidden="true"></i>
                        Keys {{ min($currentPageStart + 5, $currentPageEnd) }}-{{ $currentPageEnd }}
                    </h4>
                    @foreach (array_slice($apiKeys, 5, 5) as $index => $api)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center space-x-3">
                                    <div
                                        class="w-4 h-4 rounded-full {{ $api['is_active'] ? 'bg-green-500' : 'bg-red-500' }}">
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900">API Key #{{ $api['display_index'] }}</div>
                                        <div class="text-sm text-gray-500 font-mono">{{ $api['preview'] }}</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $api['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $api['is_active'] ? 'Active' : 'Error' }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-sm">
                                <div class="text-gray-600">
                                    <i class="fas fa-clock mr-1" aria-hidden="true"></i>
                                    Last Test: <span class="font-medium">{{ $api['last_test'] ?: 'Never' }}</span>
                                </div>
                                <button class="js-test-api cursor-pointer" data-key-index="{{ $api['index'] }}"
                                    class="inline-flex cursor-pointer items-center px-3 py-1 border border-blue-300 text-blue-600 hover:bg-blue-50 rounded-md text-xs font-medium transition-colors">
                                    <i class="fas fa-play mr-1" aria-hidden="true"></i>
                                    Test
                                </button>
                            </div>

                            @if (!$api['is_active'] && isset($api['error_message']))
                                <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-700">
                                    <i class="fas fa-exclamation-triangle mr-1" aria-hidden="true"></i>
                                    {{ $api['error_message'] }}
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <!-- Empty state for second column if needed -->
                    @if (count($apiKeys) <= 5)
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                            <i class="fas fa-plus-circle text-gray-400 text-2xl mb-2" aria-hidden="true"></i>
                            <p class="text-gray-500 text-sm">Additional API keys will appear here</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-3 bg-green-50 rounded-lg">
                        <div class="text-2xl font-bold text-green-600">{{ $activeKeysCount }}</div>
                        <div class="text-sm text-green-700">Active Keys</div>
                    </div>
                    <div class="text-center p-3 bg-red-50 rounded-lg">
                        <div class="text-2xl font-bold text-red-600">{{ $errorKeysCount }}</div>
                        <div class="text-sm text-red-700">Error Keys</div>
                    </div>
                    <div class="text-center p-3 bg-blue-50 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600">{{ $totalApiKeys }}</div>
                        <div class="text-sm text-blue-700">Total Keys</div>
                    </div>
                    <div class="text-center p-3 bg-yellow-50 rounded-lg">
                        <div class="text-2xl font-bold text-yellow-600">{{ $untestedKeysCount }}</div>
                        <div class="text-sm text-yellow-700">Untested</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Queue & Document Stats -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">

            <!-- Queue Statistics -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Queue Statistics</h3>
                    <button data-action="refreshQueueStats"
                        class="text-blue-600 cursor-pointer hover:text-blue-800 text-sm">
                        <i class="fas fa-sync mr-1" aria-hidden="true"></i>Refresh
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-clock text-orange-500" aria-hidden="true"></i>
                            <span class="text-gray-700">Pending Jobs</span>
                        </div>
                        <span
                            class="font-bold text-lg {{ $queueStats['pending_jobs'] > 10 ? 'text-orange-600' : 'text-gray-900' }}">
                            {{ $queueStats['pending_jobs'] }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-exclamation-circle text-red-500" aria-hidden="true"></i>
                            <span class="text-gray-700">Failed Jobs</span>
                        </div>
                        <span
                            class="font-bold text-lg {{ $queueStats['failed_jobs'] > 5 ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $queueStats['failed_jobs'] }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-chart-line text-blue-500" aria-hidden="true"></i>
                            <span class="text-gray-700">Jobs (Last Hour)</span>
                        </div>
                        <span class="font-bold text-lg text-gray-900">{{ $queueStats['jobs_last_hour'] }}</span>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-times-circle text-red-500" aria-hidden="true"></i>
                            <span class="text-gray-700">Failed (Last Hour)</span>
                        </div>
                        <span
                            class="font-bold text-lg {{ $queueStats['failed_last_hour'] > 2 ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $queueStats['failed_last_hour'] }}
                        </span>
                    </div>
                </div>

                @if ($queueStats['failed_jobs'] > 0)
                    <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-red-800">
                                <i class="fas fa-exclamation-triangle mr-1" aria-hidden="true"></i>
                                <strong>Warning:</strong> {{ $queueStats['failed_jobs'] }} failed jobs detected.
                            </div>
                            <button onclick="showFailedJobs()" class="text-red-600 hover:text-red-800 text-sm underline">
                                View Details
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Document Statistics -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Document Statistics</h3>
                    <a href="{{ route('admin.documents.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                        <i class="fas fa-external-link-alt mr-1" aria-hidden="true"></i>View All
                    </a>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <div class="text-3xl font-bold text-blue-600">{{ $documentStats['total'] }}</div>
                        <div class="text-sm text-blue-700 mt-1">Total Documents</div>
                    </div>
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <div class="text-3xl font-bold text-green-600">{{ $documentStats['completed'] }}</div>
                        <div class="text-sm text-green-700 mt-1">Completed</div>
                    </div>
                    <div class="text-center p-4 bg-yellow-50 rounded-lg">
                        <div class="text-3xl font-bold text-yellow-600">{{ $documentStats['processing'] }}</div>
                        <div class="text-sm text-yellow-700 mt-1">Processing</div>
                    </div>
                    <div class="text-center p-4 bg-red-50 rounded-lg">
                        <div class="text-3xl font-bold text-red-600">{{ $documentStats['failed'] }}</div>
                        <div class="text-sm text-red-700 mt-1">Failed</div>
                    </div>
                </div>

                @if ($documentStats['stuck'] > 0)
                    <div class="mt-4 p-3 bg-orange-50 border border-orange-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-orange-800">
                                <i class="fas fa-exclamation-triangle mr-1" aria-hidden="true"></i>
                                <strong>Alert:</strong> {{ $documentStats['stuck'] }} documents appear to be stuck.
                            </div>
                            <button data-action="resetStuckDocuments"
                                class="text-orange-600 hover:text-orange-800 text-sm underline">
                                Reset Now
                            </button>
                        </div>
                    </div>
                @endif

                <div class="mt-4 text-sm text-gray-600 text-center">
                    <i class="fas fa-calendar mr-1" aria-hidden="true"></i>
                    {{ $documentStats['today'] }} documents uploaded today
                </div>
            </div>
        </div>

        <!-- System Commands -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">System Commands</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <button data-action="runCommand" data-command='api:check-status'
                    class="p-4 border cursor-pointer border-gray-300 rounded-lg hover:bg-gray-50 hover:border-blue-300 text-left transition-colors">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-search text-blue-500 mr-2" aria-hidden="true"></i>
                        <div class="font-medium text-gray-900">Check API Status</div>
                    </div>
                    <div class="text-sm text-gray-600">Test all API keys and check quotas</div>
                </button>

                <button data-action="runCommand" data-command='documents:process-stuck'
                    class="p-4 border cursor-pointer border-gray-300 rounded-lg hover:bg-gray-50 hover:border-orange-300 text-left transition-colors">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-redo text-orange-500 mr-2" aria-hidden="true"></i>
                        <div class="font-medium text-gray-900">Process Stuck Documents</div>
                    </div>
                    <div class="text-sm text-gray-600">Requeue stuck or failed documents</div>
                </button>

                <button data-action="runCommand" data-command='queue:restart'
                    class="p-4 border cursor-pointer border-gray-300 rounded-lg hover:bg-gray-50 hover:border-green-300 text-left transition-colors">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-sync text-green-500 mr-2" aria-hidden="true"></i>
                        <div class="font-medium text-gray-900">Restart Queue Workers</div>
                    </div>
                    <div class="text-sm text-gray-600">Restart all queue workers</div>
                </button>
            </div>
        </div>
    </div>

    <!-- Command Output Modal -->
    <div id="command-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl mx-4 w-full max-h-[80vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Command Output</h3>
                <button data-action="closeCommandModal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl" aria-hidden="true"></i>
                </button>
            </div>

            <div id="command-output" class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm overflow-x-auto">
                <div class="animate-pulse">Running command...</div>
            </div>
        </div>
    </div>



    {{-- Add this to the head section of api-monitor.blade.php --}}
    @push('styles')
        <style>
            /* API Monitor Specific Styles */
            .api-key-card {
                transition: all 0.2s ease-in-out;
            }

            .api-key-card:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }

            .status-indicator {
                position: relative;
                animation: pulse 2s infinite;
            }

            .status-indicator.active {
                animation: none;
            }

            .status-indicator.error {
                animation: pulse-red 2s infinite;
            }

            @keyframes pulse {
                0% {
                    box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
                }

                70% {
                    box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
                }

                100% {
                    box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
                }
            }

            @keyframes pulse-red {
                0% {
                    box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
                }

                70% {
                    box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
                }

                100% {
                    box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
                }
            }

            .pagination-button {
                transition: all 0.2s ease-in-out;
            }

            .pagination-button:hover:not(:disabled) {
                transform: scale(1.05);
            }

            .pagination-button:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }

            /* Loading states */
            .loading-spinner {
                display: inline-block;
                width: 16px;
                height: 16px;
                border: 2px solid #f3f3f3;
                border-top: 2px solid #3498db;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                0% {
                    transform: rotate(0deg);
                }

                100% {
                    transform: rotate(360deg);
                }
            }

            /* Stats cards animations */
            .stats-card {
                transition: all 0.3s ease-in-out;
            }

            .stats-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            }

            /* Command buttons */
            .command-button {
                transition: all 0.2s ease-in-out;
                position: relative;
                overflow: hidden;
            }

            .command-button:hover {
                transform: translateY(-1px);
            }

            .command-button::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
                transition: left 0.5s;
            }

            .command-button:hover::before {
                left: 100%;
            }

            /* Modal animations */
            .modal-enter {
                animation: modal-fade-in 0.3s ease-out;
            }

            .modal-leave {
                animation: modal-fade-out 0.3s ease-in;
            }

            @keyframes modal-fade-in {
                from {
                    opacity: 0;
                    transform: scale(0.9);
                }

                to {
                    opacity: 1;
                    transform: scale(1);
                }
            }

            @keyframes modal-fade-out {
                from {
                    opacity: 1;
                    transform: scale(1);
                }

                to {
                    opacity: 0;
                    transform: scale(0.9);
                }
            }

            /* Responsive design improvements */
            @media (max-width: 768px) {
                .api-keys-grid {
                    grid-template-columns: 1fr;
                }

                .stats-grid {
                    grid-template-columns: repeat(2, 1fr);
                }

                .command-grid {
                    grid-template-columns: 1fr;
                }
            }

            /* Progress bar animations */
            .progress-bar {
                background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
                background-size: 400% 400%;
                animation: gradient-shift 3s ease infinite;
            }

            @keyframes gradient-shift {
                0% {
                    background-position: 0% 50%;
                }

                50% {
                    background-position: 100% 50%;
                }

                100% {
                    background-position: 0% 50%;
                }
            }

            /* Error message styling */
            .error-message {
                background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
                border-left: 4px solid #ef4444;
            }

            .success-message {
                background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
                border-left: 4px solid #22c55e;
            }

            /* Key preview styling */
            .key-preview {
                font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
                background: #f8fafc;
                padding: 2px 6px;
                border-radius: 4px;
                border: 1px solid #e2e8f0;
            }

            /* Status badge improvements */
            .status-badge {
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            }

            .status-active {
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: white;
            }

            .status-error {
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                color: white;
            }

            .status-untested {
                background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                color: white;
            }

            /* Column headers */
            .column-header {
                background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                border-bottom: 2px solid #3b82f6;
                position: sticky;
                top: 0;
                z-index: 10;
            }

            /* Empty state styling */
            .empty-state {
                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                border: 2px dashed #cbd5e1;
                transition: all 0.3s ease;
            }

            .empty-state:hover {
                border-color: #3b82f6;
                background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            }

            /* Dark mode support */
            @media (prefers-color-scheme: dark) {
                .api-key-card {
                    background: #1f2937;
                    border-color: #374151;
                }

                .key-preview {
                    background: #111827;
                    border-color: #374151;
                    color: #e5e7eb;
                }

                .column-header {
                    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
                    color: #e5e7eb;
                }
            }

            /* Accessibility improvements */
            .focus-visible:focus {
                outline: 2px solid #3b82f6;
                outline-offset: 2px;
                box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
            }

            /* Print styles */
            @media print {

                .no-print,
                .pagination-button,
                .command-button,
                button {
                    display: none !important;
                }

                .api-key-card {
                    break-inside: avoid;
                    box-shadow: none;
                    border: 1px solid #000;
                }

                .stats-card {
                    box-shadow: none;
                    border: 1px solid #000;
                }
            }
        </style>
    @endpush
@endsection
