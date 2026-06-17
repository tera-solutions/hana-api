<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lesson templates inside a plan (lesson-plan.md §6, §16).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_lesson_plan_lessons', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lesson_plan_id')->constrained('edu_lesson_plans')->cascadeOnDelete();

            $table->integer('lesson_no');
            $table->string('lesson_title');

            $table->text('objective')->nullable();
            $table->text('vocabulary')->nullable();
            $table->text('grammar')->nullable();
            $table->text('activities')->nullable();
            $table->text('homework')->nullable();
            $table->integer('duration')->nullable(); // minutes

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            // BR002: lesson_no is unique within a plan.
            $table->unique(['lesson_plan_id', 'lesson_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_lesson_plan_lessons');
    }
};
