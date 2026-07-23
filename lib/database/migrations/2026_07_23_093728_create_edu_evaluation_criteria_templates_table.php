<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A named list of criteria (rubric) to pre-fill when creating an Evaluation —
 * `edu_evaluations.criteria` already accepts any free-text criterion string
 * (evaluation.md), so this is purely a reusable template layer on top, not a
 * new validation constraint. `is_shared` templates (admin-only to create) are
 * visible business-wide; non-shared ones are private to their creator, so a
 * teacher can self-define their own without affecting anyone else.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_evaluation_criteria_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('sys_business')->cascadeOnDelete();

            $table->string('evaluation_type'); // teacher | student | parent
            $table->string('name');
            $table->json('criteria'); // ["Chuyên môn", "Phương pháp giảng dạy", ...]

            $table->boolean('is_shared')->default(false);
            $table->string('status')->default('active');

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            $table->index(['business_id', 'evaluation_type', 'status'], 'eval_criteria_templates_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_evaluation_criteria_templates');
    }
};
