<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('documents')->orderBy('id')->chunkById(1000, function ($rows) {
            foreach ($rows as $row) {
                DB::table('documents')
                    ->where('id', $row->id)
                    ->update([
                        'mp3_content' => '',
                        'flac_content' => '',
                        'mp3_path' => 'documents/' . $row->id . '/audio.mp3',
                        'cover_path' => '' . $row->year . '/' . $row->id . '-cover.jpg',
                        'file_path' => 'files/' . $row->year . '/' . $row->id . '-document.pdf',
                    ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
