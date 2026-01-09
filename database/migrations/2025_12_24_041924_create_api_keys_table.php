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
        Schema::create('gemini_api_keys', function (Blueprint $table) {
            $table->id();

            $table->string('label')->nullable();
            $table->string('account_email', 191)->nullable()->index();

            // store encrypted key (see Model cast below)
            $table->text('api_key');

            $table->boolean('is_enabled')->default(true)->index();
            $table->string('status', 20)->default('untested')->index();

            $table->timestamp('last_test_at')->nullable()->index();
            $table->string('last_error')->nullable();
            $table->unsignedSmallInteger('last_response_code')->nullable();
            $table->float('last_response_time')->nullable();

            $table->unsignedInteger('tested_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gemini_api_keys');
    }
};
