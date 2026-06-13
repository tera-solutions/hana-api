<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_teachers', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained('sys_branches')->nullOnDelete();

            $table->string('avatar')->nullable()->after('name');
            $table->string('gender')->nullable()->after('avatar');
            $table->date('dob')->nullable()->after('gender');
            $table->string('email')->nullable()->after('dob');
            $table->string('phone')->nullable()->after('email');
            $table->string('identity_no')->nullable()->after('phone');
            $table->text('address')->nullable()->after('identity_no');

            $table->string('employment_type')->nullable()->after('type');
            $table->decimal('monthly_salary', 18, 2)->nullable()->after('salary_per_hour');
            $table->foreignId('manager_id')->nullable()->after('monthly_salary')->constrained('users')->nullOnDelete();

            $table->date('joined_at')->nullable()->after('status');
            $table->date('resigned_at')->nullable()->after('joined_at');
            $table->text('note')->nullable()->after('resigned_at');

            $table->index(['branch_id', 'status']);
        });

        // Align column names with teacher.md §12.
        Schema::table('hr_teachers', function (Blueprint $table) {
            $table->renameColumn('name', 'full_name');
            $table->renameColumn('type', 'teacher_type');
            $table->renameColumn('salary_per_hour', 'hourly_rate');
        });
    }

    public function down(): void
    {
        Schema::table('hr_teachers', function (Blueprint $table) {
            $table->renameColumn('full_name', 'name');
            $table->renameColumn('teacher_type', 'type');
            $table->renameColumn('hourly_rate', 'salary_per_hour');
        });

        Schema::table('hr_teachers', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'status']);
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('manager_id');
            $table->dropColumn([
                'avatar', 'gender', 'dob', 'email', 'phone', 'identity_no', 'address',
                'employment_type', 'monthly_salary', 'joined_at', 'resigned_at', 'note',
            ]);
        });
    }
};
