<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_teacher_skills', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_id')->constrained('hr_teachers')->cascadeOnDelete();

            $table->string('skill_name');
            $table->string('level')->nullable(); // beginner, intermediate, expert...

            $table->timestamps();

            $table->unique(['teacher_id', 'skill_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_teacher_skills');
    }
};
