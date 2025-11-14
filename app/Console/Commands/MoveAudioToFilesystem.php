<?php

// app/Console/Commands/MoveAudioToFilesystem.php
namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MoveAudioToFilesystem extends Command
{
    protected $signature = 'documents:move-audio 
                            {--purge : Null out blob columns after writing files}
                            {--chunk=500 : Chunk size}';

    protected $description = 'Move mp3/flac blobs to filesystem and save paths';

    public function handle(): int
    {
        $disk = Storage::disk('documents');
        $chunk = (int)$this->option('chunk');
        $purge = (bool)$this->option('purge');

        Document::query()
            ->where(function ($q) {
                $q->whereNotNull('mp3_content')
                    ->orWhereNotNull('flac_content');
            })
            ->orderBy('id')
            ->chunkById($chunk, function ($docs) use ($disk, $purge) {
                DB::transaction(function () use ($docs, $disk, $purge) {
                    foreach ($docs as $doc) {
                        $year = $doc->year ?: date('Y');
                        $slug = $doc->slug ?? $doc->id;

                        // MP3
                        if ($doc->mp3_content && empty($doc->mp3_path)) {
                            $mp3Path = "audio/mp3/{$year}/{$doc->id}-{$slug}.mp3";
                            $disk->put($mp3Path, $doc->mp3_content);
                            $doc->mp3_path = $mp3Path;
                            $doc->mp3_checksum = hash('sha256', $doc->mp3_content);
                        }

                        // FLAC
                        if ($doc->flac_content && empty($doc->flac_path)) {
                            $flacPath = "audio/flac/{$year}/{$doc->id}-{$slug}.flac";
                            $disk->put($flacPath, $doc->flac_content);
                            $doc->flac_path = $flacPath;
                            $doc->flac_checksum = hash('sha256', $doc->flac_content);
                        }

                        if ($purge) {
                            // Keep until final cutover if you want belt-and-suspenders
                            $doc->mp3_content = null;
                            $doc->flac_content = null;
                        }

                        $doc->saveQuietly();
                        $this->line("Processed #{$doc->id}");
                    }
                });
            });

        $this->info('Done.');
        return self::SUCCESS;
    }
}
