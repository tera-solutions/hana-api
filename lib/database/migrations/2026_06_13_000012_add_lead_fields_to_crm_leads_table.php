<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('id');
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained('sys_branches')->nullOnDelete();

            $table->string('gender')->nullable()->after('name');
            $table->date('dob')->nullable()->after('gender');

            // "Người phụ trách" — the staff member who owns the lead.
            $table->foreignId('owner_id')->nullable()->after('source')->constrained('users')->nullOnDelete();

            $table->text('note')->nullable()->after('status');

            // Suspension bookkeeping (lead.md §6 / §7).
            $table->string('previous_status')->nullable()->after('note');
            $table->timestamp('suspended_at')->nullable()->after('previous_status');
            $table->text('suspend_reason')->nullable()->after('suspended_at');
            $table->foreignId('suspended_by')->nullable()->after('suspend_reason')->constrained('users')->nullOnDelete();

            $table->index('code');
            $table->index(['business_id', 'branch_id', 'status']);
            $table->index('owner_id');
            $table->auditColumns();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('owner_id');
            $table->dropConstrainedForeignId('suspended_by');

            $table->dropIndex(['code']);
            $table->dropIndex(['business_id', 'branch_id', 'status']);
            $table->dropIndex(['owner_id']);
            $table->dropUnique(['code']);

            $table->dropAuditColumns();
            $table->dropSoftDeletes();

            $table->dropColumn([
                'code',
                'gender',
                'dob',
                'note',
                'previous_status',
                'suspended_at',
                'suspend_reason',
            ]);
        });
    }
};
