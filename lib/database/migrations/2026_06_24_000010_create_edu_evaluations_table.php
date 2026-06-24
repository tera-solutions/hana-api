<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Evaluations (đánh giá) — teacher / student / parent (evaluation.md §X). A single
 * polymorphic table: target_id / evaluator_id are typed by evaluation_type /
 * evaluator_type; the per-criterion scores live in `criteria` (JSON) and the total
 * `score` + `classification` are derived from them (BR-03).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_evaluations', function (Blueprint $table) {
            $table->id();

            $table->string('evaluation_code')->unique();
            $table->string('evaluation_type'); // teacher, student, parent
            $table->unsignedBigInteger('target_id'); // teacher / student / parent id, by type

            $table->string('evaluator_type'); // parent, student, manager, teacher, cskh
            $table->unsignedBigInteger('evaluator_id')->nullable();

            $table->foreignId('course_id')->nullable()->constrained('edu_courses')->nullOnDelete();
            $table->foreignId('class_room_id')->nullable()->constrained('edu_classes')->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('edu_lessons')->nullOnDelete();

            $table->string('evaluation_period'); // session, lesson, midterm, final, course, monthly, quarterly

            $table->json('criteria')->nullable(); // [{ criterion, score(1-5) }, ...]
            $table->decimal('score', 5, 2)->nullable(); // total, auto-computed (BR-03)
            $table->string('classification')->nullable(); // excellent, good, average, weak, warning

            $table->text('comment')->nullable();
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->text('recommendations')->nullable();

            $table->string('status')->default('draft'); // draft, submitted, approved, rejected, locked
            $table->timestamp('evaluated_at')->nullable();

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            $table->index(['evaluation_type', 'target_id']);
            $table->index(['evaluator_type', 'evaluator_id']);
            $table->index(['class_room_id', 'status']);
            $table->index('course_id');
            $table->index('lesson_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_evaluations');
    }
};
