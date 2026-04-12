<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('edu_exam_question', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exam_id')->constrained('edu_exams')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('edu_questions')->cascadeOnDelete();

            $table->integer('order')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_exam_question');
    }
};