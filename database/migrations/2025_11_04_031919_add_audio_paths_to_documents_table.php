<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// database/migrations/xxxx_xx_xx_xxxxxx_add_audio_paths_to_documents_table.php
return new class extends Migration {
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('mp3_path')->nullable()->after('mp3_content');
            $table->string('flac_path')->nullable()->after('flac_content');

            // Optional (good for integrity / dedupe):
            $table->string('mp3_checksum', 64)->nullable()->after('mp3_path');  // sha256
            $table->string('flac_checksum', 64)->nullable()->after('flac_path');

            $table->index('mp3_path');
            $table->index('flac_path');
        });
    }
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['mp3_path', 'flac_path', 'mp3_checksum', 'flac_checksum']);
        });
    }
};
