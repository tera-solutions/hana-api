<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Version history of a lesson plan (lesson-plan.md §13, §16).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_lesson_plan_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lesson_plan_id')->constrained('edu_lesson_plans')->cascadeOnDelete();

            $table->integer('version');
            $table->text('change_summary')->nullable();

            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();

            $table->timestamps();

            $table->index(['lesson_plan_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_lesson_plan_versions');
    }
};
