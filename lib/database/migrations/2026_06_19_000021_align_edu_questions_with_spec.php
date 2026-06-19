<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Align the legacy edu_questions scaffold with question.md (§VI, §XV): bank metadata
 * (code/skill/difficulty/category), scoring, explanation, academic metadata, versioning,
 * lifecycle status and audit/soft-delete columns. The bare `type` becomes `question_type`;
 * the inline `options`/`answer` columns are superseded by edu_question_answers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_questions', function (Blueprint $table) {
            $table->renameColumn('type', 'question_type');
        });

        Schema::table('edu_questions', function (Blueprint $table) {
            $table->dropColumn(['options', 'answer']);
        });

        Schema::table('edu_questions', function (Blueprint $table) {
            $table->string('question_code')->nullable()->unique()->after('id');
            $table->string('skill')->nullable()->after('question_type');
            $table->string('difficulty')->nullable()->after('skill');
            $table->foreignId('category_id')->nullable()->after('level_id')->constrained('edu_question_categories')->nullOnDelete();
            $table->decimal('score', 8, 2)->default(0)->after('content');
            $table->text('explanation')->nullable()->after('score');

            // Academic metadata (question.md §VI).
            $table->string('cefr_level')->nullable();
            $table->string('cambridge_level')->nullable();
            $table->string('learning_objective')->nullable();
            $table->string('grammar_topic')->nullable();
            $table->string('vocabulary_topic')->nullable();

            $table->unsignedInteger('version')->default(1);
            $table->string('status')->default('draft'); // draft, reviewing, approved, active, archived

            $table->auditColumns();
            $table->softDeletes();

            $table->index(['skill', 'difficulty', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('edu_questions', function (Blueprint $table) {
            $table->dropIndex(['skill', 'difficulty', 'status']);
            $table->dropUnique(['question_code']);
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn([
                'question_code', 'skill', 'difficulty', 'score', 'explanation',
                'cefr_level', 'cambridge_level', 'learning_objective', 'grammar_topic', 'vocabulary_topic',
                'version', 'status',
            ]);
            $table->dropAuditColumns();
            $table->dropSoftDeletes();
        });

        Schema::table('edu_questions', function (Blueprint $table) {
            $table->json('options')->nullable();
            $table->text('answer')->nullable();
            $table->renameColumn('question_type', 'type');
        });
    }
};
