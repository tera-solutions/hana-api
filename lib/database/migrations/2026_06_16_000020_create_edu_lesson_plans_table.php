<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lesson plans / teaching templates per course + level (lesson-plan.md §16).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_lesson_plans', function (Blueprint $table) {
            $table->id();

            $table->string('plan_code')->unique();
            $table->string('plan_name');

            $table->foreignId('course_id')->constrained('edu_courses')->cascadeOnDelete();
            $table->foreignId('level_id')->nullable()->constrained('edu_levels')->nullOnDelete();

            $table->integer('version')->default(1);
            $table->integer('total_lessons')->default(0);
            $table->text('description')->nullable();

            $table->string('status')->default('draft'); // draft, reviewing, published, archived

            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            $table->index(['course_id', 'status']);
            $table->index(['level_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_lesson_plans');
    }
};
