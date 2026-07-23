<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `edu_levels` had no tenant column at all — every business could list/view/
 * edit every other business's levels (no BusinessScope to enforce). This adds
 * the column and backfills it from each level's course (which is already
 * business-scoped), so levels become shared within one business, not global.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_levels', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained('sys_business')->nullOnDelete();
        });

        // Portable across MySQL/SQLite (tests): no cross-table UPDATE JOIN.
        DB::table('edu_courses')->select('id', 'business_id')
            ->whereNotNull('business_id')
            ->orderBy('id')
            ->chunk(200, function ($courses) {
                foreach ($courses as $course) {
                    DB::table('edu_levels')
                        ->where('course_id', $course->id)
                        ->update(['business_id' => $course->business_id]);
                }
            });

        Schema::table('edu_levels', function (Blueprint $table) {
            $table->index(['business_id']);
        });
    }

    public function down(): void
    {
        Schema::table('edu_levels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_id');
        });
    }
};
