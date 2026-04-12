<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('edu_course_material', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_id')->constrained('edu_courses')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('edu_materials')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['course_id', 'material_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_course_material');
    }
};