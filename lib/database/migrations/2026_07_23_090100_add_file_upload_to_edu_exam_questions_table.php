<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supports the "paper_upload" question type (exam.md addendum): the question
 * content is a scanned/typed paper attached as a file instead of typed text.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_exam_questions', function (Blueprint $table) {
            $table->text('content')->nullable()->change();
            $table->unsignedBigInteger('file_id')->nullable()->after('content');
            $table->string('file_name')->nullable()->after('file_id');
        });
    }

    public function down(): void
    {
        Schema::table('edu_exam_questions', function (Blueprint $table) {
            $table->dropColumn(['file_id', 'file_name']);
            $table->text('content')->nullable(false)->change();
        });
    }
};
