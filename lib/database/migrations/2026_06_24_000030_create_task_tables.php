<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task management (task-management.md §XII): internal work items with checklists,
 * comments and attachments. A task may be linked to a business document via the
 * polymorphic related_type / related_id pair.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_tasks', function (Blueprint $table) {
            $table->id();

            $table->string('task_code')->unique();
            $table->string('title');
            $table->text('description')->nullable();

            $table->string('category');
            $table->string('priority');
            $table->string('status')->default('draft');
            $table->unsignedTinyInteger('progress')->default(0);

            $table->date('start_date');
            $table->date('due_date');
            $table->timestamp('completed_at')->nullable();

            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            $table->index(['status', 'priority']);
            $table->index(['assignee_id', 'status']);
            $table->index(['related_type', 'related_id']);
            $table->index('due_date');
        });

        Schema::create('task_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('task_tasks')->cascadeOnDelete();

            $table->string('title');
            $table->boolean('is_completed')->default(false);
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['task_id', 'is_completed']);
        });

        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('task_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('comment');

            $table->timestamps();

            $table->index('task_id');
        });

        Schema::create('task_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('task_tasks')->cascadeOnDelete();
            $table->foreignId('file_id')->constrained('media')->cascadeOnDelete();

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->index('task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_attachments');
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('task_checklists');
        Schema::dropIfExists('task_tasks');
    }
};
