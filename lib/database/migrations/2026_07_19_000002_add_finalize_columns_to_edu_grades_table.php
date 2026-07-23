<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `edu_grades` is used both for raw component scores (`type` = a score-config
 * component key, e.g. "midterm") and for the computed final row (`type` =
 * "final"). `breakdown`/`finalized_*` are only populated on the final row.
 * The table has no existing rows (nothing writes to it yet), so the unique
 * constraint is safe to add directly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_grades', function (Blueprint $table) {
            $table->json('breakdown')->nullable()->after('type');
            $table->timestamp('finalized_at')->nullable()->after('breakdown');
            $table->unsignedBigInteger('finalized_by')->nullable()->after('finalized_at');

            $table->unique(['class_id', 'student_id', 'type'], 'edu_grades_class_student_type_unique');
        });
    }

    public function down(): void
    {
        Schema::table('edu_grades', function (Blueprint $table) {
            $table->dropUnique('edu_grades_class_student_type_unique');
            $table->dropColumn(['breakdown', 'finalized_at', 'finalized_by']);
        });
    }
};
