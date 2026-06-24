<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Status-transition audit trail for a leave request (leave-request.md §XVI).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_leave_request_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('leave_request_id')->constrained('edu_leave_requests')->cascadeOnDelete();

            $table->string('action');
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->text('note')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('leave_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_leave_request_logs');
    }
};
