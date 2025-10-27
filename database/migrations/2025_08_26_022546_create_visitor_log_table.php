<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('visitor_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address');
            $table->string('user_agent')->nullable();
            $table->string('page_visited');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('document_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action')->nullable(); // 'view', 'download', 'search'
            $table->json('search_data')->nullable();
            $table->timestamp('created_at');

            $table->index(['ip_address', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('visitor_logs');
    }
};
