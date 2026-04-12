<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('edu_class_teacher', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_id')->constrained('edu_classes')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('hr_teachers')->cascadeOnDelete();

            $table->string('role')->default('main'); // main, assistant

            $table->timestamps();

            $table->unique(['class_id', 'teacher_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_class_teacher');
    }
};