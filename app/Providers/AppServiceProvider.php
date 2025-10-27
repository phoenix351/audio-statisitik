<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use App\Services\TextToSpeechService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobFailed;
use App\Services\TextExtractionService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register custom services
        $this->app->singleton(TextExtractionService::class, function ($app) {
            return new TextExtractionService();
        });

        $this->app->singleton(TextToSpeechService::class, function ($app) {
            return new TextToSpeechService();
        });
    }

    public function boot(): void
    {
        if($this->app->environment('production')) {
            \URL::forceScheme('https');
        }
        
        // Enhanced PHP settings for document processing
        ini_set('max_execution_time', 7200);    // 2 hours
        ini_set('memory_limit', '2048M');       // 2GB RAM
        ini_set('max_input_time', 1200);        // 20 minutes for input
        ini_set('post_max_size', '50M');        // 50MB POST
        ini_set('upload_max_filesize', '50M');  // 50MB upload
        
        // Force queue connection to database
        config(['queue.default' => 'database']);
        
        // Register queue event listeners for better monitoring
        $this->registerQueueEventListeners();

        // Share audio assets configuration
        View::share('audioAssets', [
            'enhanced_system' => asset('js/app.js'),
            'admin_manager' => asset('js/admin-audio-manager.js'),
            'grid_manager' => asset('js/grid-audio-manager.js'),
        ]);
    }
    
    private function registerQueueEventListeners(): void
    {
        Queue::before(function (JobProcessing $event) {
            Log::info("🚀 [Queue] Job starting", [
                'job_id' => $event->job->getJobId(),
                'job_name' => $event->job->getName(),
                'queue' => $event->job->getQueue(),
                'attempts' => $event->job->attempts(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true)
            ]);
        });

        Queue::after(function (JobProcessed $event) {
            Log::info("✅ [Queue] Job completed", [
                'job_id' => $event->job->getJobId(),
                'job_name' => $event->job->getName(),
                'queue' => $event->job->getQueue(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true)
            ]);
        });

        Queue::failing(function (JobFailed $event) {
            Log::error("💥 [Queue] Job failed", [
                'job_id' => $event->job->getJobId(),
                'job_name' => $event->job->getName(),
                'queue' => $event->job->getQueue(),
                'exception' => $event->exception->getMessage(),
                'attempts' => $event->job->attempts(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true)
            ]);
        });
    }
}