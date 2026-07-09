<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parent/student ratings of a teacher, backing the teacher achievement
 * screen (rating rate, average rating, satisfaction rate, review list).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_teacher_reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_id')->constrained('hr_teachers')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('edu_students')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('edu_classes')->nullOnDelete();

            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('content')->nullable();

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            $table->index(['teacher_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_teacher_reviews');
    }
};
