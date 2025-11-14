<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audio_settings', function (Blueprint $table) {
            $table->id();
            $table->string('voice_model')->default('default');
            $table->float('speech_rate', 3, 2)->default(1.0);
            $table->integer('audio_quality')->default(128); // kbps
            $table->json('tts_config')->nullable();
            $table->boolean('auto_play')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('audio_settings');
    }
};
