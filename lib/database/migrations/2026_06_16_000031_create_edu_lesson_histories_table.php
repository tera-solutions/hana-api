<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail for lesson changes: reschedule, teacher change, lock, unlock
 * (lesson.md §13 Audit Log tab, §16).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_lesson_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lesson_id')->constrained('edu_lessons')->cascadeOnDelete();

            $table->string('action'); // reschedule, change_teacher, cancel, lock, unlock
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('reason')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['lesson_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_lesson_histories');
    }
};
