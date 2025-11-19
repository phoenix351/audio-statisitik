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
        Schema::table('documents', function (Blueprint $table) {
            // 1. drop foreign key dulu
            $table->dropForeign(['indicator_id']);
        });

        Schema::table('documents', function (Blueprint $table) {
            // 2. jadikan nullable
            $table->unsignedBigInteger('indicator_id')->nullable()->change();
        });

        Schema::table('documents', function (Blueprint $table) {
            // 3. tambahkan lagi FK, tapi kalau indicator dihapus -> set null
            $table->foreign('indicator_id')
                ->references('id')
                ->on('indicators')
                ->nullOnDelete(); // atau ->cascadeOnDelete() kalau mau
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['indicator_id']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedBigInteger('indicator_id')->nullable(false)->change();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('indicator_id')
                ->references('id')
                ->on('indicators')
                ->cascadeOnDelete();
        });
    }
};
