<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new columns if they don't exist
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'view_count')) {
                $table->integer('view_count')->default(0)->after('play_count');
            }
        });

        // Update legacy documents metadata
        $this->updateLegacyDocumentsMetadata();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'view_count')) {
                $table->dropColumn('view_count');
            }
        });
    }

    /**
     * Update metadata for legacy documents
     */
    private function updateLegacyDocumentsMetadata(): void
    {
        try {
            Log::info('Starting legacy documents metadata update...');

            // Find documents that need metadata updates
            $legacyDocuments = DB::table('documents')
                ->where('status', 'completed')
                ->whereNotNull('mp3_content')
                ->where(function($query) {
                    $query->whereNull('processing_metadata')
                          ->orWhereRaw("JSON_EXTRACT(processing_metadata, '$.processed_via') IS NULL")
                          ->orWhereRaw("JSON_EXTRACT(processing_metadata, '$.processed_via') != 'queue'");
                })
                ->get();

            $updated = 0;
            $errors = 0;

            Log::info("Found {$legacyDocuments->count()} legacy documents to update");

            foreach ($legacyDocuments as $document) {
                try {
                    $currentMetadata = json_decode($document->processing_metadata, true) ?? [];
                    
                    // Create updated metadata with required fields
                    $updatedMetadata = array_merge($currentMetadata, [
                        'processed_via' => 'legacy',
                        'completion_status' => 'success',
                        'mp3_size' => $document->mp3_content ? strlen($document->mp3_content) : 0,
                        'flac_size' => $document->flac_content ? strlen($document->flac_content) : 0,
                        'audio_duration_seconds' => floatval($document->audio_duration ?? 0),
                        'migrated_at' => now()->toISOString(),
                        'migration_version' => '1.0',
                        'file_type' => $document->file_mime_type ?? 'application/pdf',
                        'processing_time_seconds' => $currentMetadata['processing_time_seconds'] ?? 0
                    ]);

                    // Ensure audio_format is set
                    if (!isset($updatedMetadata['audio_format'])) {
                        $formats = [];
                        if ($document->mp3_content) $formats[] = 'mp3';
                        if ($document->flac_content) $formats[] = 'flac';
                        $updatedMetadata['audio_format'] = implode('/', $formats);
                    }

                    // Update the document
                    DB::table('documents')
                        ->where('id', $document->id)
                        ->update([
                            'processing_metadata' => json_encode($updatedMetadata),
                            'updated_at' => now()
                        ]);

                    $updated++;

                    if ($updated % 10 == 0) {
                        Log::info("Updated {$updated} documents so far...");
                    }

                } catch (\Exception $e) {
                    Log::warning('Failed to update document metadata', [
                        'document_id' => $document->id,
                        'error' => $e->getMessage()
                    ]);
                    $errors++;
                }
            }

            Log::info('Legacy documents metadata update completed', [
                'total_found' => $legacyDocuments->count(),
                'updated' => $updated,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update legacy documents metadata', [
                'error' => $e->getMessage()
            ]);
        }
    }
};