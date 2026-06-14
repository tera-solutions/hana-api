<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suspension (bảo lưu) history for an enrollment (enrollment.md §9 / §14).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_enrollment_suspensions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enrollment_id')->constrained('edu_enrollments')->cascadeOnDelete();

            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason');

            $table->timestamps();
            $table->auditColumns();

            $table->index('enrollment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_enrollment_suspensions');
    }
};
