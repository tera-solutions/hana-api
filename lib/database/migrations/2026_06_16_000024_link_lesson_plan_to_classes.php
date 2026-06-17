<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link classes to a lesson plan (lesson-plan.md §17). The generated teaching
 * records live in edu_lessons (lesson.md §16) and carry their own snapshot of the
 * source template, so edu_sessions is intentionally left untouched here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_classes', function (Blueprint $table) {
            $table->foreignId('lesson_plan_id')->nullable()->after('course_id')->constrained('edu_lesson_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('edu_classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lesson_plan_id');
        });
    }
};
