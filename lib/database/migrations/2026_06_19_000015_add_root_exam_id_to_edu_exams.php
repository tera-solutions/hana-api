<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Version lineage for exams (exam.md §IV "Version đề thi"). Every version of the same
 * logical exam shares a root: the first version's row. NULL means a standalone exam.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_exams', function (Blueprint $table) {
            $table->foreignId('root_exam_id')->nullable()->after('version')->constrained('edu_exams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('edu_exams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('root_exam_id');
        });
    }
};
