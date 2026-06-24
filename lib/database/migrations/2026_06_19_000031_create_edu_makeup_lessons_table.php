<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Make-up entitlement raised when an approved student leave qualifies for a make-up
 * session (leave-request.md §X).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_makeup_lessons', function (Blueprint $table) {
            $table->id();

            $table->foreignId('leave_request_id')->constrained('edu_leave_requests')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('edu_students')->cascadeOnDelete();
            $table->foreignId('original_lesson_id')->nullable()->constrained('edu_lessons')->nullOnDelete();
            $table->foreignId('makeup_lesson_id')->nullable()->constrained('edu_lessons')->nullOnDelete();

            $table->string('status')->default('waiting'); // waiting, scheduled, completed, expired

            $table->timestamps();
            $table->auditColumns();

            $table->index(['leave_request_id', 'status']);
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_makeup_lessons');
    }
};
