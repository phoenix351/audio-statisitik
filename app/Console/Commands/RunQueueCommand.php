<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Jalankan queue worker sekali (untuk cron job di cPanel)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Jalankan queue sekali
        Artisan::call('queue:work --once --queue=default --tries=3');

        $this->info("Queue worker dijalankan sekali pada: " . now()->format('d-m-Y H:i:s'));
    }
}
