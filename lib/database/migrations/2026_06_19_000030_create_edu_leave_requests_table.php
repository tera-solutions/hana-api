<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Student / teacher leave requests (leave-request.md §XVI). Lifecycle is status-based
 * (pending → approved/rejected/cancelled/completed); no soft delete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_leave_requests', function (Blueprint $table) {
            $table->id();

            $table->string('request_code')->unique();
            $table->string('request_type');   // student_leave | teacher_leave
            $table->string('requester_type'); // student | teacher
            $table->unsignedBigInteger('requester_id');

            $table->foreignId('class_room_id')->nullable()->constrained('edu_classes')->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('edu_lessons')->nullOnDelete();

            $table->date('leave_date');
            $table->string('reason_type');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('attachment_file_id')->nullable();

            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled, completed

            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
            $table->auditColumns();

            $table->index(['requester_type', 'requester_id']);
            $table->index(['class_room_id', 'status']);
            $table->index('lesson_id');
            $table->index('leave_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_leave_requests');
    }
};
