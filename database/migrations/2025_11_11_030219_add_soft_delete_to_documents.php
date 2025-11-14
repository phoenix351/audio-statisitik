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
            if (!Schema::hasColumn('documents', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->after('updated_at')->index();
            }
            if (!Schema::hasColumn('documents', 'deleted_by')) {
                $table->timestamp('deleted_by')->nullable()->after('deleted_at')->index();
            }
            if (!Schema::hasColumn('documents', 'deleted_reason')) {
                $table->timestamp('deleted_reason')->nullable()->after('deleted_by')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'deleted_at')) $table->dropColumn('deleted_at');
            if (Schema::hasColumn('documents', 'deleted_by')) $table->dropColumn('deleted_by');
            if (Schema::hasColumn('documents', 'deleted_reason')) $table->dropColumn('deleted_reason');
        });
    }
};
