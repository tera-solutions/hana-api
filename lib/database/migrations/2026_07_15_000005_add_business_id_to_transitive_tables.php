<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Give the last tenant-owned tables their own business_id so they can be
 * isolated by BusinessScope directly, instead of only transitively through a
 * parent lookup. Backfilled from each table's owning parent.
 *
 * table => [parent table, local FK, parent key]
 */
return new class extends Migration
{
    private const TABLES = [
        'edu_class_teacher' => ['edu_classes', 'class_id', 'id'],
        'edu_sessions' => ['edu_classes', 'class_id', 'id'],
        'hr_contracts' => ['hr_teachers', 'teacher_id', 'id'],
        'hr_reviews' => ['hr_teachers', 'teacher_id', 'id'],
        'edu_exam_results' => ['edu_students', 'student_id', 'id'],
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

            // Correlated-subquery backfill: valid on both MySQL and SQLite.
            DB::statement(
                "UPDATE {$table} SET business_id = ("
                ."SELECT {$parent}.business_id FROM {$parent} WHERE {$parent}.{$parentKey} = {$table}.{$fk}"
                .') WHERE business_id IS NULL'
            );
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::TABLES) as $table) {
            if (Schema::hasColumn($table, 'business_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropIndex(['business_id']);
                    $t->dropColumn('business_id');
                });
            }
        }
    }
};
