<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Promote the minimal edu_enrollments table to the "hợp đồng học tập" model of
 * enrollment.md §14: identification code, course/sales links, the lesson package,
 * and the financial snapshot (tuition / discount / paid / debt).
 *
 * Written defensively (column/index guards) so it can be re-run after a partial
 * apply — MySQL auto-commits each DDL statement, so a mid-migration failure
 * leaves earlier statements in place.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The column block is all-or-nothing; `code` is its sentinel.
        if (! Schema::hasColumn('edu_enrollments', 'code')) {
            Schema::table('edu_enrollments', function (Blueprint $table) {
                $table->string('code')->nullable()->unique()->after('id');

                $table->unsignedBigInteger('course_id')->nullable()->after('student_id');
                $table->unsignedBigInteger('sales_id')->nullable()->after('class_id');

                $table->integer('total_lessons')->default(0)->after('enrolled_at');
                $table->integer('completed_lessons')->default(0)->after('total_lessons');
                $table->integer('remaining_lessons')->default(0)->after('completed_lessons');

                $table->decimal('price_per_lesson', 12, 2)->default(0)->after('remaining_lessons');
                $table->decimal('tuition_amount', 12, 2)->default(0)->after('price_per_lesson');
                $table->decimal('discount_amount', 12, 2)->default(0)->after('tuition_amount');
                $table->decimal('paid_amount', 12, 2)->default(0)->after('discount_amount');
                $table->decimal('debt_amount', 12, 2)->default(0)->after('paid_amount');

                $table->text('note')->nullable()->after('status');

                $table->auditColumns();
                $table->softDeletes();

                $table->foreign('course_id')->references('id')->on('edu_courses')->nullOnDelete();
                $table->foreign('sales_id')->references('id')->on('users')->nullOnDelete();

                $table->index(['course_id', 'status']);
                $table->index(['status', 'debt_amount']);
            });
        }

        $indexes = collect(Schema::getIndexes('edu_enrollments'))->pluck('name')->all();

        // The composite unique is the only index backing the student_id foreign
        // key, so MySQL blocks its removal. Give the FK a standalone index first.
        if (! in_array('edu_enrollments_student_id_index', $indexes, true)) {
            Schema::table('edu_enrollments', fn (Blueprint $table) => $table->index('student_id'));
        }

        // A student may re-enrol in the same class after cancelling/refunding, so
        // the rigid uniqueness is replaced by an app-layer "no active duplicate" rule.
        if (in_array('edu_enrollments_student_id_class_id_unique', $indexes, true)) {
            Schema::table('edu_enrollments', fn (Blueprint $table) => $table->dropUnique(['student_id', 'class_id']));
        }

        // Entry state is "pending" per enrollment.md §15.
        Schema::table('edu_enrollments', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('edu_enrollments', function (Blueprint $table) {
            $table->string('status')->default('active')->change();
        });

        $indexes = collect(Schema::getIndexes('edu_enrollments'))->pluck('name')->all();

        if (! in_array('edu_enrollments_student_id_class_id_unique', $indexes, true)) {
            Schema::table('edu_enrollments', fn (Blueprint $table) => $table->unique(['student_id', 'class_id']));
        }

        Schema::table('edu_enrollments', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropForeign(['sales_id']);
            $table->dropIndex(['course_id', 'status']);
            $table->dropIndex(['status', 'debt_amount']);
            $table->dropIndex(['student_id']);
            $table->dropSoftDeletes();
            $table->dropAuditColumns();
            $table->dropColumn([
                'code', 'course_id', 'sales_id',
                'total_lessons', 'completed_lessons', 'remaining_lessons',
                'price_per_lesson', 'tuition_amount', 'discount_amount',
                'paid_amount', 'debt_amount', 'note',
            ]);
        });
    }
};
