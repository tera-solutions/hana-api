<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Materials attached to a lesson template (lesson-plan.md §14, §16). file_id is a
 * plain reference: there is no file-storage table in the schema yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_lesson_plan_materials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lesson_plan_lesson_id')->constrained('edu_lesson_plan_lessons')->cascadeOnDelete();

            $table->unsignedBigInteger('file_id');
            $table->string('material_type'); // pdf, video, audio, slide, worksheet, homework

            $table->timestamps();
            $table->auditColumns();

            $table->index('lesson_plan_lesson_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_lesson_plan_materials');
    }
};
