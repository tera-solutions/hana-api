<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_placement_test_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('placement_test_id')->constrained('edu_placement_tests')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('edu_students')->cascadeOnDelete();

            $table->decimal('score', 5, 2)->nullable();
            $table->string('cefr_result')->nullable();
            $table->unsignedTinyInteger('completion_rate')->default(0);
            $table->string('status')->default('in_progress'); // in_progress, completed

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->auditColumns();

            $table->index(['placement_test_id', 'status']);
            $table->unique(['placement_test_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_placement_test_results');
    }
};
