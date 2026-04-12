<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('edu_exams', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->foreignId('course_id')->nullable()->constrained('edu_courses')->nullOnDelete();

            $table->integer('duration'); // minutes

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_exams');
    }
};