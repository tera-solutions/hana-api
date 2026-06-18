<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the free-text `level` string with a `level_id` FK into edu_levels,
     * aligning students with courses/lesson_plans/assignments.
     */
    public function up(): void
    {
        Schema::table('edu_students', function (Blueprint $table) {
            $table->foreignId('level_id')->nullable()->after('level')->constrained('edu_levels')->nullOnDelete();
        });

        // Backfill from the legacy string by matching the level catalog name.
        // Correlated subquery keeps this portable across MySQL and SQLite.
        DB::table('edu_students')->whereNotNull('level')->update([
            'level_id' => DB::raw('(select id from edu_levels where edu_levels.name = edu_students.level)'),
        ]);

        Schema::table('edu_students', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }

    public function down(): void
    {
        Schema::table('edu_students', function (Blueprint $table) {
            $table->string('level')->nullable()->after('gender');
        });

        DB::table('edu_students')->whereNotNull('level_id')->update([
            'level' => DB::raw('(select name from edu_levels where edu_levels.id = edu_students.level_id)'),
        ]);

        Schema::table('edu_students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('level_id');
        });
    }
};
