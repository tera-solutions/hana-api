<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which kind of evidence backed a level change (exam | evaluation | other),
 * for teacher-app-085's "Căn cứ" field. `exam_result_id` already existed and
 * links to `edu_exam_results`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_student_level_histories', function (Blueprint $table) {
            $table->string('reason_type')->nullable()->after('action');
        });
    }

    public function down(): void
    {
        Schema::table('edu_student_level_histories', function (Blueprint $table) {
            $table->dropColumn('reason_type');
        });
    }
};
