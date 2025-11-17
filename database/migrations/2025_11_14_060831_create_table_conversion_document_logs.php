<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_conversion_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();

            // optional, if you have users who trigger uploads
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // which job / step
            $table->string('job_name')->nullable();        // e.g. 'PdfToTextJob', 'TextToTtsJob'
            $table->string('stage')->nullable();           // e.g. 'pdf_uploaded', 'text_extracted', 'tts_started', ...
            $table->string('status')->default('info');     // info | success | warning | error

            $table->text('message')->nullable();           // human-readable message
            $table->json('meta')->nullable();              // extra debug info (exception, durations, file path, etc.)

            $table->string('queue_job_id')->nullable();    // $this->job?->getJobId()
            $table->string('queue_name')->nullable();      // $this->job?->getQueue()

            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_conversion_logs');
    }
};
