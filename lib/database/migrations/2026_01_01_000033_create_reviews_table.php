<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hr_reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained('edu_students')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('hr_teachers')->nullOnDelete();

            $table->text('content');
            $table->string('rating')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_reviews');
    }
};