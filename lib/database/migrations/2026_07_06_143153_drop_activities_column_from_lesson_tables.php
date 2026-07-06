<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * activities is now edu_lesson_plan_lesson_activities / edu_lesson_activities (structured rows).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_lesson_plan_lessons', function (Blueprint $table) {
            $table->dropColumn('activities');
        });

        Schema::table('edu_lessons', function (Blueprint $table) {
            $table->dropColumn('activities');
        });
    }

    public function down(): void
    {
        Schema::table('edu_lesson_plan_lessons', function (Blueprint $table) {
            $table->text('activities')->nullable();
        });

        Schema::table('edu_lessons', function (Blueprint $table) {
            $table->text('activities')->nullable();
        });
    }
};
