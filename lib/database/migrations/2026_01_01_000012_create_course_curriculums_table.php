<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('edu_course_curriculums', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_id')->constrained('edu_courses')->cascadeOnDelete();

            $table->string('title');
            $table->integer('order');

            $table->text('content')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_course_curriculums');
    }
};