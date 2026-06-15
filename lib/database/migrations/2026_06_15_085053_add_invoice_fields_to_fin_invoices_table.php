<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Broaden fin_invoices from receivable-only to the full RECEIVABLE/PAYABLE model
 * (invoice.md §III): invoice type, polymorphic partner, branch, tax and the
 * running paid/balance amounts. student_id becomes optional for payable invoices.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fin_invoices', function (Blueprint $table) {
            $table->string('invoice_type')->default('receivable')->after('business_id');

            $table->string('partner_type')->nullable()->after('invoice_type');
            $table->unsignedBigInteger('partner_id')->nullable()->after('partner_type');

            $table->foreignId('branch_id')->nullable()->after('business_id')
                ->constrained('sys_branches')->nullOnDelete();

            $table->date('invoice_date')->nullable()->after('total');

            $table->decimal('tax', 12, 2)->default(0)->after('discount');
            $table->decimal('paid_amount', 12, 2)->default(0)->after('total');
            $table->decimal('balance_amount', 12, 2)->default(0)->after('paid_amount');

            // Audit stamps for HasAuditFields.
            $table->unsignedBigInteger('created_by')->nullable()->after('note');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');

            $table->index('invoice_type');
            $table->index(['partner_type', 'partner_id']);
        });

        Schema::table('fin_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('fin_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropIndex(['invoice_type']);
            $table->dropIndex(['partner_type', 'partner_id']);
            $table->dropColumn([
                'invoice_type', 'partner_type', 'partner_id',
                'invoice_date', 'tax', 'paid_amount', 'balance_amount',
                'created_by', 'updated_by', 'deleted_by',
            ]);
        });

        Schema::table('fin_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id')->nullable(false)->change();
        });
    }
};
