<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('hr_attendance_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('hr_attendances')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('edu_students')->cascadeOnDelete();

            $table->string('status');
            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(['attendance_id', 'student_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('hr_attendance_details');
    }
};