@extends('layouts.app')

@section('title', 'Failed Jobs - Admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center space-x-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-blue-600">Dashboard</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <a href="{{ route('admin.api-monitor') }}" class="hover:text-blue-600">API Monitor</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <span>Failed Jobs</span>
        </div>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Failed Jobs</h1>
                <p class="text-gray-600 mt-2">View and manage failed queue jobs</p>
            </div>
            <div class="flex space-x-3">
                <button onclick="retryAllJobs()" 
                        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                    <i class="fas fa-redo mr-2" aria-hidden="true"></i>
                    Retry All
                </button>
                <button onclick="clearAllJobs()" 
                        class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                    <i class="fas fa-trash mr-2" aria-hidden="true"></i>
                    Clear All
                </button>
            </div>
        </div>
    </div>

    @if($failedJobs->isEmpty())
        <!-- Empty State -->
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600 text-2xl" aria-hidden="true"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Failed Jobs</h3>
            <p class="text-gray-600">All queue jobs are running successfully!</p>
            <a href="{{ route('admin.api-monitor') }}" 
               class="inline-flex items-center mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>
                Back to API Monitor
            </a>
        </div>
    @else
        <!-- Failed Jobs Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">
                        Failed Jobs ({{ $failedJobs->count() }})
                    </h3>
                    <div class="text-sm text-gray-500">
                        Last 50 failed jobs
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Job
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Queue
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Failed At
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Error
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($failedJobs as $job)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-red-500 rounded-full mr-3"></div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ class_basename(json_decode($job->payload)->displayName ?? 'Unknown Job') }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            ID: {{ $job->uuid }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $job->queue }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div>{{ \Carbon\Carbon::parse($job->failed_at)->format('M d, Y H:i') }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ \Carbon\Carbon::parse($job->failed_at)->diffForHumans() }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-red-600 max-w-xs">
                                    <button onclick="showError('{{ $job->uuid }}')" 
                                            class="text-left hover:text-red-800 cursor-pointer">
                                        {{ Str::limit($job->exception, 100) }}
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <button onclick="retryJob('{{ $job->uuid }}')" 
                                            class="text-green-600 hover:text-green-800">
                                        <i class="fas fa-redo" aria-hidden="true"></i>
                                    </button>
                                    <button onclick="deleteJob('{{ $job->uuid }}')" 
                                            class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<!-- Error Details Modal -->
<div id="error-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-4xl mx-4 w-full max-h-[80vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Error Details</h3>
            <button onclick="closeErrorModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl" aria-hidden="true"></i>
            </button>
        </div>
        
        <div id="error-content" class="bg-red-50 border border-red-200 rounded-lg p-4">
            <pre class="text-sm text-red-800 whitespace-pre-wrap overflow-x-auto"></pre>
        </div>
    </div>
</div>

@push('scripts')
<script>
const failedJobsData = @json($failedJobs);

function showError(jobUuid) {
    const job = failedJobsData.find(j => j.uuid === jobUuid);
    if (job) {
        document.querySelector('#error-content pre').textContent = job.exception;
        document.getElementById('error-modal').classList.remove('hidden');
    }
}

function closeErrorModal() {
    document.getElementById('error-modal').classList.add('hidden');
}

async function retryJob(jobUuid) {
    if (!confirm('Retry this failed job?')) return;
    
    try {
        const response = await fetch(`/admin/queue/retry-job/${jobUuid}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        if (response.ok) {
            showToast('Job retried successfully', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast('Failed to retry job', 'error');
        }
    } catch (error) {
        showToast('Error retrying job: ' + error.message, 'error');
    }
}

async function deleteJob(jobUuid) {
    if (!confirm('Delete this failed job? This action cannot be undone.')) return;
    
    try {
        const response = await fetch(`/admin/queue/delete-job/${jobUuid}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        if (response.ok) {
            showToast('Job deleted successfully', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast('Failed to delete job', 'error');
        }
    } catch (error) {
        showToast('Error deleting job: ' + error.message, 'error');
    }
}

async function retryAllJobs() {
    if (!confirm('Retry all failed jobs? This may take some time.')) return;
    
    try {
        const response = await fetch('/admin/queue/retry-all', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        if (response.ok) {
            showToast('All jobs retried successfully', 'success');
            setTimeout(() => window.location.reload(), 2000);
        } else {
            showToast('Failed to retry all jobs', 'error');
        }
    } catch (error) {
        showToast('Error retrying all jobs: ' + error.message, 'error');
    }
}

async function clearAllJobs() {
    if (!confirm('Delete all failed jobs? This action cannot be undone.')) return;
    
    try {
        const response = await fetch('/admin/queue/clear-all', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        if (response.ok) {
            showToast('All failed jobs cleared', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast('Failed to clear all jobs', 'error');
        }
    } catch (error) {
        showToast('Error clearing all jobs: ' + error.message, 'error');
    }
}

// Toast utility (fallback)
function showToast(message, type) {
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
    } else {
        alert(`${type.toUpperCase()}: ${message}`);
    }
}
</script>
@endpush
@endsection