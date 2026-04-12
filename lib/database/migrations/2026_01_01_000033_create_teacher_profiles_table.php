<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hr_teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('hr_teachers')->cascadeOnDelete();

            $table->string('degree')->nullable();
            $table->string('experience')->nullable();
            $table->text('bio')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_teacher_profiles');
    }
};