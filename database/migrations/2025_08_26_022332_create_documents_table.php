<?php
// database/migrations/2024_01_03_000001_create_documents_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('type', ['publication', 'brs']);
            $table->year('year');
            $table->foreignId('indicator_id')->constrained()->onDelete('cascade');
            $table->text('description')->nullable();

            // File storage (dummy binary dulu, nanti ALTER jadi LONGBLOB/MEDIUMBLOB)
            $table->binary('file_content')->nullable(); // akan diubah ke LONGBLOB
            $table->string('file_name'); // Original filename
            $table->string('file_mime_type'); // MIME type
            $table->integer('file_size')->nullable();

            $table->binary('cover_image')->nullable(); // akan diubah ke MEDIUMBLOB
            $table->string('cover_mime_type')->nullable();

            // Text & audio content
            $table->longText('extracted_text')->nullable();
            $table->binary('mp3_content')->nullable();  // akan diubah ke LONGBLOB
            $table->binary('flac_content')->nullable(); // akan diubah ke LONGBLOB
            $table->integer('audio_duration')->nullable(); // in seconds

            // Processing metadata
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->json('processing_metadata')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_completed_at')->nullable();

            // Statistics
            $table->integer('download_count')->default(0);
            $table->integer('play_count')->default(0);
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Indexes
            $table->index(['type', 'year', 'indicator_id']);
            $table->index(['status', 'is_active']);
            $table->index(['created_at']);
        });

        // Ubah kolom binary ke MEDIUMBLOB / LONGBLOB sesuai kebutuhan
        DB::statement("ALTER TABLE documents MODIFY file_content LONGBLOB NULL");
        DB::statement("ALTER TABLE documents MODIFY cover_image MEDIUMBLOB NULL");
        DB::statement("ALTER TABLE documents MODIFY mp3_content LONGBLOB NULL");
        DB::statement("ALTER TABLE documents MODIFY flac_content LONGBLOB NULL");
    }

    public function down()
    {
        Schema::dropIfExists('documents');
    }
};
