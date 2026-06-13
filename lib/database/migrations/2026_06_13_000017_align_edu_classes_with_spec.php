<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_classes', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('id');

            $table->unsignedBigInteger('assignee_id')->nullable()->after('business_id');
            $table->unsignedBigInteger('teacher_id')->nullable()->after('assignee_id');
            $table->unsignedBigInteger('room_id')->nullable()->after('teacher_id');

            $table->string('learning_type')->default('scheduled')->after('name');

            $table->integer('min_warning_capacity')->nullable()->after('max_students');
            $table->integer('min_capacity')->nullable()->after('min_warning_capacity');
            $table->integer('max_warning_capacity')->nullable()->after('min_capacity');

            $table->boolean('use_course_curriculum')->default(false)->after('max_students');
            $table->text('description')->nullable()->after('use_course_curriculum');
            $table->text('note')->nullable()->after('description');

            $table->softDeletes();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('assignee_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('teacher_id')->references('id')->on('hr_teachers')->nullOnDelete();

            $table->index(['course_id', 'status']);
            $table->index(['teacher_id', 'status']);
        });

        // Rename max_students → max_capacity (separate call required on some DBs).
        Schema::table('edu_classes', function (Blueprint $table) {
            $table->renameColumn('max_students', 'max_capacity');
        });

        // Change status default to 'draft' (spec §13).
        Schema::table('edu_classes', function (Blueprint $table) {
            $table->string('status')->default('draft')->change();
        });

        // Rename day_of_week → weekday in schedules (spec §12).
        Schema::table('edu_class_schedules', function (Blueprint $table) {
            $table->renameColumn('day_of_week', 'weekday');
        });
    }

    public function down(): void
    {
        Schema::table('edu_class_schedules', function (Blueprint $table) {
            $table->renameColumn('weekday', 'day_of_week');
        });

        Schema::table('edu_classes', function (Blueprint $table) {
            $table->string('status')->default('opening')->change();
        });

        Schema::table('edu_classes', function (Blueprint $table) {
            $table->renameColumn('max_capacity', 'max_students');
        });

        Schema::table('edu_classes', function (Blueprint $table) {
            $table->dropForeign(['assignee_id']);
            $table->dropForeign(['teacher_id']);
            $table->dropIndex(['course_id', 'status']);
            $table->dropIndex(['teacher_id', 'status']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'code', 'assignee_id', 'teacher_id', 'room_id', 'learning_type',
                'min_warning_capacity', 'min_capacity', 'max_warning_capacity',
                'use_course_curriculum', 'description', 'note',
                'created_by', 'updated_by', 'deleted_by',
            ]);
        });
    }
};
