<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Files attached to a submission (assignment.md §8, §15). file_id is a plain
 * reference — there is no file-storage table in the schema yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_assignment_submission_files', function (Blueprint $table) {
            $table->id();

            $table->foreignId('submission_id')->constrained('edu_assignment_submissions')->cascadeOnDelete();
            $table->unsignedBigInteger('file_id');
            $table->string('file_name')->nullable();

            $table->timestamps();

            $table->index('submission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_assignment_submission_files');
    }
};
