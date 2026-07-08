<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Structured activities for a generated lesson, replacing edu_lessons.activities.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_lesson_activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lesson_id')->constrained('edu_lessons')->cascadeOnDelete();

            $table->integer('sort_order')->default(1);
            $table->string('avatar')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('duration')->nullable(); // minutes
            $table->string('status')->default('pending'); // pending, in_progress, completed

            $table->timestamps();
            $table->auditColumns();

            $table->index(['lesson_id', 'sort_order'], 'lesson_activities_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_lesson_activities');
    }
};
