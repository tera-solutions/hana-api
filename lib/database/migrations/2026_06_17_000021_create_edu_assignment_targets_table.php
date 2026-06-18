<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Students an assignment is given to (assignment.md §7, §15). Assigning a student
 * also seeds an ASSIGNED submission (BR004).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_assignment_targets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assignment_id')->constrained('edu_assignments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('edu_students')->cascadeOnDelete();
            $table->timestamp('assigned_at')->nullable();

            $table->timestamps();

            $table->unique(['assignment_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_assignment_targets');
    }
};
