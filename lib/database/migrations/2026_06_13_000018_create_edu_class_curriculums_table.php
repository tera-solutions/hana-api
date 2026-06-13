<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class-level curriculum, cloned from the course curriculum when a class is
 * created with `use_course_curriculum = true` (spec §5). Mirrors
 * edu_course_curriculums plus a link back to the source row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_class_curriculums', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_id')->constrained('edu_classes')->cascadeOnDelete();
            $table->foreignId('course_curriculum_id')->nullable()->constrained('edu_course_curriculums')->nullOnDelete();

            $table->string('title');
            $table->integer('order')->default(0);
            $table->text('content')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_class_curriculums');
    }
};
