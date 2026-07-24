<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional per-criterion score-level descriptions (e.g. `{"pronunciation":
 * {"1": "Yếu", "5": "Xuất sắc"}}`) — purely informational metadata for the
 * template's fixed 1-5 scale (Evaluation's scoring stays unchanged, see
 * EvaluationService::withComputedScore()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_evaluation_criteria_templates', function (Blueprint $table) {
            $table->json('criteria_descriptions')->nullable()->after('criteria');
        });
    }

    public function down(): void
    {
        Schema::table('edu_evaluation_criteria_templates', function (Blueprint $table) {
            $table->dropColumn('criteria_descriptions');
        });
    }
};
