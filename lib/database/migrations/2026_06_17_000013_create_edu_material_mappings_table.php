<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Polymorphic links between a material and a business entity (material.md §9, §14):
 * COURSE / LESSON_PLAN / LESSON / ASSIGNMENT / EVALUATION. BR007: one material may
 * be linked in many places, so (material, entity) is unique but a material repeats.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_material_mappings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('material_id')->constrained('edu_materials')->cascadeOnDelete();
            $table->string('entity_type'); // course, lesson_plan, lesson, assignment, evaluation
            $table->unsignedBigInteger('entity_id');

            $table->timestamps();
            $table->auditColumns();

            $table->unique(['material_id', 'entity_type', 'entity_id']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_material_mappings');
    }
};
