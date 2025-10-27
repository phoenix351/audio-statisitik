<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule::command('queue:run-worker')
//     ->everyMinute()
//     ->withoutOverlapping()
//     ->runInBackground()
//     ->onFailure(function () {
//         \Log::error('❌ RunQueueWorker gagal dijalankan.');
//     })
//     ->onSuccess(function () {
//         \Log::info('✅ RunQueueWorker berhasil dijalankan via scheduler.');
//     });

// Schedule::call(function () {
//     \Log::info("🕒 Scheduler test berjalan pada " . now());
// })->everyMinute();


// // Tes cron apakah jalan
// Schedule::call(function () {
//     Log::channel('cron')->info("✅ Cron test jalan pada " . now());
// })->everyMinute();