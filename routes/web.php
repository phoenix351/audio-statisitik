<?php

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AudioController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\QueueController;
use App\Http\Controllers\Admin\ProgressController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\IndicatorController;
use App\Http\Controllers\Admin\ApiMonitorController;
use App\Http\Controllers\Admin\DocumentManagementController;

// Public routes (no authentication required)
Route::view('/unsupported', 'unsupported')->name('unsupported');
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [HomeController::class, 'search'])->name('search');
Route::get('/publikasi', [DocumentController::class, 'publications'])->name('documents.publications');
Route::get('/brs', [DocumentController::class, 'brs'])->name('documents.brs');
// Route::get('/document/{document}', [DocumentController::class, 'show'])->name('documents.show');

// Document Routes
Route::prefix('documents')->name('documents.')->group(function () {
    // Existing document routes
    Route::get('/', [DocumentController::class, 'index'])->name('index');
    Route::get('/{document:slug}', [DocumentController::class, 'show'])->name('show');
    Route::get('/{document}/download', [DocumentController::class, 'download'])->name('download');
    Route::get('/{document}/cover', [DocumentController::class, 'cover'])->name('cover');
    Route::get('/uuid/{uuid}', [DocumentManagementController::class, 'uuid_show'])->name('uuid.show');


    // New audio-related routes
    Route::get('/audio/list', [DocumentController::class, 'indexWithAudio'])->name('audio.list');
    Route::post('/audio/playlist', [DocumentController::class, 'getAudioPlaylist'])->name('audio.playlist');
    Route::get('/{document}/audio/{format}/stream', [DocumentController::class, 'streamAudio'])
        ->name('documents.audio.stream')
        ->where('format', 'mp3|flac');

    // Legacy metadata update routes (admin only)
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/metadata/update-legacy/{document}', [DocumentController::class, 'updateLegacyMetadata'])->name('metadata.update-legacy');
        Route::post('/metadata/batch-update-legacy', [DocumentController::class, 'batchUpdateLegacyMetadata'])->name('metadata.batch-update-legacy');

        //recycle bin

    });
});

// Audio Player Routes
Route::prefix('audio')->name('audio.')->group(function () {
    // Get audio metadata
    // Route::get('metadata/{document}', [AudioController::class, 'getAudioMetadata'])
    //      ->name('metadata');

    // Stream audio content
    Route::get('stream/{document}/{format?}', [AudioController::class, 'streamAudio'])
        ->name('stream')
        ->where('format', 'mp3|flac');

    // // Get playlist data for multiple documents
    // Route::post('playlist', [AudioController::class, 'getPlaylistData'])
    //      ->name('playlist');

    // // Update playback progress
    // Route::post('progress/{document}', [AudioController::class, 'updateProgress'])
    //      ->name('progress.update');

    // // Get playback progress
    // Route::get('progress/{document}', [AudioController::class, 'getProgress'])
    //      ->name('progress.get');
});

Route::get('/documents/{document}/audio/{format}/download', [DocumentController::class, 'downloadAudio'])
    ->name('documents.audio.download')
    ->where('format', 'mp3|flac');

// Authentication routes (only for admins)
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin routes (protected by auth and admin middleware)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api-bps', function () {
        return view('admin.api-bps');
    })->name('api-bps');

    // Document management
    Route::resource('documents', DocumentManagementController::class);
    Route::post('/documents/{document}/reprocess', [DocumentManagementController::class, 'reprocess'])->name('documents.reprocess');
    Route::get('/documents/{document}/status', [DocumentManagementController::class, 'getStatus'])->name('documents.status');
    Route::get('/recycle-bin', [DocumentManagementController::class, 'recycleBin'])->name('recycle-bin');
    Route::post('/recycle-bin/restore', [DocumentManagementController::class, 'restoreBin'])->name('restore-bin');
    Route::post('/recycle-bin/force-delete', [DocumentManagementController::class, 'forceDeleteBin'])->name('force-delete-bin');
    // Progress monitoring
    Route::get('/progress/all', [ProgressController::class, 'getAllProgress'])->name('progress.all');
    Route::get('/progress/{document}', [ProgressController::class, 'getDocumentProgress'])->name('progress.document');
    Route::get('/progress/stats', [ProgressController::class, 'getStats'])->name('progress.stats');

    // Other admin routes
    // Route::resource('indicators', IndicatorController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    // Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    // Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // API Monitor routes
    Route::get('/api-monitor', [ApiMonitorController::class, 'index'])->name('api-monitor');
    Route::post('/api-monitor/test-key', [ApiMonitorController::class, 'testKey'])->name('api-monitor.test-key');
    Route::post('/api-monitor/test-all-keys', [ApiMonitorController::class, 'testAllKeys'])->name('api-monitor.test-all');
    Route::post('/api-monitor/reset-stuck', [ApiMonitorController::class, 'resetStuckDocuments'])->name('api-monitor.reset-stuck');
    Route::get('/api-monitor/queue-stats', [ApiMonitorController::class, 'getQueueStats'])->name('api-monitor.queue-stats');
    Route::post('/api-monitor/refresh-status', [ApiMonitorController::class, 'refreshApiStatus'])->name('api-monitor.refresh');

    // Failed Jobs Management
    Route::get('/failed-jobs', [QueueController::class, 'failedJobs'])->name('failed-jobs');
    Route::post('/retry-job/{uuid}', [QueueController::class, 'retryJob'])->name('retry-job');
    Route::delete('/delete-job/{uuid}', [QueueController::class, 'deleteJob'])->name('delete-job');
    Route::post('/retry-all', [QueueController::class, 'retryAll'])->name('retry-all');
    Route::delete('/clear-all', [QueueController::class, 'clearAll'])->name('clear-all');
});

// API routes for AJAX requests
Route::prefix('api')->group(function () {
    Route::get('/search-suggestions', [HomeController::class, 'searchSuggestions'])->name('api.search.suggestions');
    Route::post('/voice-search', [HomeController::class, 'voiceSearch'])->name('api.voice.search');
    Route::get('/document-status/{document}', [DocumentController::class, 'checkStatus'])->name('api.document.status');
});

// API Routes untuk Mobile/External Access
Route::prefix('api/v1')->name('api.')->middleware(['throttle:60,1'])->group(function () {

    // Public API endpoints
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [DocumentController::class, 'indexWithAudio'])->name('index');
        Route::get('/{document}/metadata', [AudioController::class, 'getAudioMetadata'])->name('metadata');
        Route::post('/playlist', [DocumentController::class, 'getAudioPlaylist'])->name('playlist');
    });

    // Audio streaming (with higher rate limit for streaming)
    Route::prefix('audio')->name('audio.')->middleware(['throttle:200,1'])->group(function () {
        Route::get('stream/{document}/{format?}', [AudioController::class, 'streamAudio'])
            ->name('stream')
            ->where('format', 'mp3|flac');
    });

    // Progress tracking (requires session or auth)
    Route::prefix('progress')->name('progress.')->group(function () {
        Route::post('/{document}', [AudioController::class, 'updateProgress'])->name('update');
        Route::get('/{document}', [AudioController::class, 'getProgress'])->name('get');
    });
});

// AJAX Routes untuk Real-time Updates
Route::middleware(['throttle:100,1'])->prefix('ajax')->name('ajax.')->group(function () {

    // Document search dengan audio filter
    Route::get('documents/search', function (Request $request) {
        return app(DocumentController::class)->indexWithAudio($request);
    })->name('documents.search');

    // Queue status check
    Route::get('documents/{document}/queue-status', function (Document $document) {
        $progressKey = "document_progress_{$document->id}";
        $progress = Cache::get($progressKey);

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $document->status,
                'progress' => $progress,
                'processing_metadata' => $document->processing_metadata
            ]
        ]);
    })->name('documents.queue-status');

    // Global audio player state
    Route::get('audio/global-state', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'active_sessions' => Cache::get('active_audio_sessions', 0),
                'popular_documents' => Cache::remember('popular_audio_documents', 300, function () {
                    return Document::where('status', 'completed')
                        ->whereNotNull('mp3_content')
                        ->orderBy('play_count', 'desc')
                        ->limit(5)
                        ->get(['id', 'title', 'play_count']);
                })
            ]
        ]);
    })->name('audio.global-state');
});
