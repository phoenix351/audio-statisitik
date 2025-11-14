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
        Schema::create('document_audit', function (Blueprint $table) {
            $table->bigIncrements('audit_id');
            $table->unsignedBigInteger('document_id');
            $table->enum('action', ['insert', 'update', 'delete', 'soft_delete', 'restore']);
            $table->unsignedBigInteger('actor_id')->nullable();  // from Laravel
            $table->string('sql_user', 128);                     // MySQL CURRENT_USER()
            $table->timestamp('occurred_at')->useCurrent();
            $table->json('old_row')->nullable();
            $table->json('new_row')->nullable();
            $table->string('reason', 255)->nullable();
            $table->index(['document_id', 'occurred_at'], 'idx_doc_time');
        });

        // (Optional) clean up first to make re-runs idempotent
        DB::unprepared('DROP TRIGGER IF EXISTS documents_bi;');
        DB::unprepared('DROP TRIGGER IF EXISTS documents_bu;');
        DB::unprepared('DROP TRIGGER IF EXISTS documents_bd;');

        // BEFORE INSERT
        DB::unprepared(<<<'SQL'
CREATE TRIGGER documents_bi
BEFORE INSERT ON documents
FOR EACH ROW
BEGIN
  INSERT INTO document_audit (document_id, action, actor_id, sql_user, old_row, new_row)
  VALUES (
    NEW.id, 'insert', @actor_id, CURRENT_USER(), NULL,
    JSON_OBJECT(
      'uuid', NEW.uuid, 'title', NEW.title, 'slug', NEW.slug,
      'type', NEW.type, 'year', NEW.year, 'indicator_id', NEW.indicator_id,
      'status', NEW.status, 'is_active', NEW.is_active
    )
  );
END
SQL);

        // BEFORE UPDATE
        DB::unprepared(<<<'SQL'
CREATE TRIGGER documents_bu
BEFORE UPDATE ON documents
FOR EACH ROW
BEGIN
  DECLARE act ENUM('update','soft_delete','restore');
  SET act = (CASE
              WHEN OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN 'soft_delete'
              WHEN OLD.deleted_at IS NOT NULL AND NEW.deleted_at IS NULL THEN 'restore'
              ELSE 'update'
            END);
  INSERT INTO document_audit (document_id, action, actor_id, sql_user, occurred_at, old_row, new_row, reason)
  VALUES (
    OLD.id, act, @actor_id, CURRENT_USER(), NOW(),
    JSON_OBJECT(
      'deleted_at', OLD.deleted_at, 'deleted_by', OLD.deleted_by, 'status', OLD.status
    ),
    JSON_OBJECT(
      'deleted_at', NEW.deleted_at, 'deleted_by', NEW.deleted_by, 'status', NEW.status
    ),
    NEW.deleted_reason
  );
END
SQL);

        // BEFORE DELETE
        DB::unprepared(<<<'SQL'
CREATE TRIGGER documents_bd
BEFORE DELETE ON documents
FOR EACH ROW
BEGIN
  INSERT INTO document_audit (document_id, action, actor_id, sql_user, old_row)
  VALUES (
    OLD.id, 'delete', @actor_id, CURRENT_USER(),
    JSON_OBJECT(
      'uuid', OLD.uuid, 'title', OLD.title, 'slug', OLD.slug,
      'type', OLD.type, 'year', OLD.year, 'indicator_id', OLD.indicator_id,
      'status', OLD.status, 'deleted_at', OLD.deleted_at
    )
  );
END
SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS documents_bi;');
        DB::unprepared('DROP TRIGGER IF EXISTS documents_bu;');
        DB::unprepared('DROP TRIGGER IF EXISTS documents_bd;');
        Schema::dropIfExists('document_audit');
    }
};
