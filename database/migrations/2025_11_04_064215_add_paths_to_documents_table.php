<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('file_path', 2048)->nullable()->after('file_size');
            $table->string('cover_path', 2048)->nullable()->after('file_path');
            // opsional: jika beralih total ke path, Anda bisa drop BLOB di migration terpisah
            $table->dropColumn(['file_content', 'cover_image']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'cover_path']);
        });
    }
};
