<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enrollment of a student into a class (spec §7 student statistics / §8 capacity).
 * Status drives the student-stats buckets: active, reserved, completed, dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_class_students', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_id')->constrained('edu_classes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('edu_students')->cascadeOnDelete();

            $table->string('status')->default('active'); // active, reserved, completed, dropped
            $table->date('enrolled_at')->nullable();

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            // A class enrolls a student at most once (enforced at app layer so it
            // coexists with soft deletes); these indexes speed the lookups.
            $table->index(['class_id', 'status']);
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_class_students');
    }
};
