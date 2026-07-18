<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LessonPlan and its children were never tenant-scoped — no `BelongsToBusiness`,
 * no `business_id` column — so any business with `lesson_plan.*` permissions
 * could view/edit/publish/archive/clone ANY other business's lesson plans by
 * ID. Same fix as 2026_07_15_000005_add_business_id_to_transitive_tables: give
 * each table its own business_id, backfilled transitively.
 *
 * table => [parent table, local FK, parent key]. Order matters: edu_lesson_plans
 * must be backfilled first since the other three derive from it.
 */
return new class extends Migration
{
    private const TABLES = [
        'edu_lesson_plans' => ['edu_courses', 'course_id', 'id'],
        'edu_lesson_plan_lessons' => ['edu_lesson_plans', 'lesson_plan_id', 'id'],
        'edu_lesson_plan_lesson_activities' => ['edu_lesson_plan_lessons', 'lesson_plan_lesson_id', 'id'],
        'edu_lesson_plan_versions' => ['edu_lesson_plans', 'lesson_plan_id', 'id'],
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table => [$parent, $fk, $parentKey]) {
            if (! Schema::hasColumn($table, 'business_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->unsignedBigInteger('business_id')->nullable();
                    $t->index('business_id');
                });
            }

            DB::statement(
                "UPDATE {$table} SET business_id = ("
                ."SELECT {$parent}.business_id FROM {$parent} WHERE {$parent}.{$parentKey} = {$table}.{$fk}"
                .') WHERE business_id IS NULL'
            );
        }
    }

    public function down(): void
    {
        foreach (array_reverse(array_keys(self::TABLES)) as $table) {
            if (Schema::hasColumn($table, 'business_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropIndex(['business_id']);
                    $t->dropColumn('business_id');
                });
            }
        }
    }
};
