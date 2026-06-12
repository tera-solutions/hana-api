<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_parents', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('id');
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained('sys_branches')->nullOnDelete();

            $table->string('gender')->nullable()->after('name');
            $table->date('dob')->nullable()->after('gender');
            $table->string('avatar')->nullable()->after('dob');

            $table->text('address')->nullable()->after('email');
            $table->string('province')->nullable()->after('address');
            $table->string('district')->nullable()->after('province');

            $table->string('occupation')->nullable()->after('district');
            $table->string('company')->nullable()->after('occupation');
            $table->text('note')->nullable()->after('company');

            $table->string('status')->default('active')->after('note');

            $table->index('code');
            $table->index(['business_id', 'branch_id', 'status']);
            $table->auditColumns();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('crm_parents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropIndex(['code']);
            $table->dropIndex(['business_id', 'branch_id', 'status']);
            $table->dropUnique(['code']);
            $table->dropAuditColumns();
            $table->dropSoftDeletes();
            $table->dropColumn([
                'code',
                'gender',
                'dob',
                'avatar',
                'address',
                'province',
                'district',
                'occupation',
                'company',
                'note',
                'status',
            ]);
        });
    }
};
