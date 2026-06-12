<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_courses', function (Blueprint $table) {
            $table->text('thumbnail')->nullable()->after('code');
            $table->text('description')->nullable()->after('price');
            $table->boolean('is_active')->default(true)->after('description');
            $table->auditColumns();
            $table->softDeletes();
        });

        // Align column names with course.md §9.
        Schema::table('edu_courses', function (Blueprint $table) {
            $table->renameColumn('duration', 'duration_minutes');
            $table->renameColumn('price', 'price_per_lesson');
        });

        // A course per course.md is standalone; program/level are no longer required.
        Schema::table('edu_courses', function (Blueprint $table) {
            $table->unsignedBigInteger('program_id')->nullable()->change();
            $table->unsignedBigInteger('level_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('edu_courses', function (Blueprint $table) {
            $table->unsignedBigInteger('program_id')->nullable(false)->change();
            $table->unsignedBigInteger('level_id')->nullable(false)->change();
        });

        Schema::table('edu_courses', function (Blueprint $table) {
            $table->renameColumn('duration_minutes', 'duration');
            $table->renameColumn('price_per_lesson', 'price');
        });

        Schema::table('edu_courses', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropAuditColumns();
            $table->dropColumn(['thumbnail', 'description', 'is_active']);
        });
    }
};
