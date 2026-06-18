<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extend the existing level-history table with the spec-021 fields (§X, §XIV): the
 * owning student_level row, the transition action and a free-text reason. Existing
 * columns (business_id/student_id/from_level_id/to_level_id/source/…) are retained
 * and still populated by the service.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_student_level_histories', function (Blueprint $table) {
            $table->foreignId('student_level_id')->nullable()->after('id')
                ->constrained('edu_student_levels')->cascadeOnDelete();
            $table->string('action')->nullable()->after('source');
            $table->text('reason')->nullable()->after('action');
        });
    }

    public function down(): void
    {
        Schema::table('edu_student_level_histories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_level_id');
            $table->dropColumn(['action', 'reason']);
        });
    }
};
