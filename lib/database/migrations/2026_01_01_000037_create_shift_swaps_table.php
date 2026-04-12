<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hr_shift_swaps', function (Blueprint $table) {
            $table->id();

            $table->foreignId('schedule_id')->constrained('hr_teaching_schedules')->cascadeOnDelete();

            $table->foreignId('from_teacher_id')->constrained('hr_teachers');
            $table->foreignId('to_teacher_id')->constrained('hr_teachers');

            $table->string('status')->default('pending'); // pending, approved

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_shift_swaps');
    }
};