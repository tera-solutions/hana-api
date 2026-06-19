<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Align the legacy edu_exams scaffold with exam.md (§VI, §XVII): the bank metadata
 * (code/type/level), scoring (total/passing), versioning, lifecycle status and the
 * audit/soft-delete columns. The original `name` column becomes `exam_name`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_exams', function (Blueprint $table) {
            $table->renameColumn('name', 'exam_name');
        });

        Schema::table('edu_exams', function (Blueprint $table) {
            $table->string('exam_code')->nullable()->unique()->after('id');
            $table->string('exam_type')->nullable()->after('exam_name');
            $table->foreignId('level_id')->nullable()->after('course_id')->constrained('edu_levels')->nullOnDelete();
            $table->decimal('total_score', 8, 2)->nullable()->after('duration');
            $table->decimal('passing_score', 8, 2)->nullable()->after('total_score');
            $table->unsignedInteger('version')->default(1)->after('passing_score');
            $table->string('status')->default('draft')->after('version');
            $table->auditColumns();
            $table->softDeletes();

            $table->index(['exam_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('edu_exams', function (Blueprint $table) {
            $table->dropIndex(['exam_type', 'status']);
            $table->dropUnique(['exam_code']);
            $table->dropConstrainedForeignId('level_id');
            $table->dropColumn(['exam_code', 'exam_type', 'total_score', 'passing_score', 'version', 'status']);
            $table->dropAuditColumns();
            $table->dropSoftDeletes();
        });

        Schema::table('edu_exams', function (Blueprint $table) {
            $table->renameColumn('exam_name', 'name');
        });
    }
};
