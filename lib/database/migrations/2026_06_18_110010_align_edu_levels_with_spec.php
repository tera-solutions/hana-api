<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reshape the minimal edu_levels stub (name/order) into the spec-021 Level master
 * (student-level.md §V, §XIV). course_id is a plain reference (no DB FK) to keep the
 * alter portable; its existence is validated at the request layer (BR002).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_levels', function (Blueprint $table) {
            $table->renameColumn('name', 'level_name');
            $table->renameColumn('order', 'level_order');
        });

        Schema::table('edu_levels', function (Blueprint $table) {
            $table->string('level_code')->nullable()->after('id');
            $table->unsignedBigInteger('course_id')->nullable()->after('level_code');
            $table->string('cefr_level')->nullable()->after('level_order');
            $table->text('description')->nullable()->after('cefr_level');
            $table->string('status')->default('active')->after('description');
        });

        // Backfill a code for any pre-existing rows so the unique index can apply.
        DB::table('edu_levels')->whereNull('level_code')->update([
            'level_code' => DB::raw('level_name'),
        ]);

        Schema::table('edu_levels', function (Blueprint $table) {
            $table->unique('level_code');
            $table->index(['course_id', 'level_order']);
        });
    }

    public function down(): void
    {
        Schema::table('edu_levels', function (Blueprint $table) {
            $table->dropUnique(['level_code']);
            $table->dropIndex(['course_id', 'level_order']);
            $table->dropColumn(['level_code', 'course_id', 'cefr_level', 'description', 'status']);
        });

        Schema::table('edu_levels', function (Blueprint $table) {
            $table->renameColumn('level_name', 'name');
            $table->renameColumn('level_order', 'order');
        });
    }
};
