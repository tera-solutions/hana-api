<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Broaden fin_payments to the full transaction model (payment.md §III): document
 * number/date/type, source fund (account_id), currency, bank reference, the
 * confirm/reverse/refund audit fields and a self-link for reverse/refund records.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fin_payments', function (Blueprint $table) {
            $table->string('payment_no')->nullable()->unique()->after('id');
            $table->date('payment_date')->nullable()->after('payment_no');
            $table->string('payment_type')->nullable()->after('payment_direction');

            $table->foreignId('branch_id')->nullable()->after('business_id')
                ->constrained('sys_branches')->nullOnDelete();

            $table->foreignId('account_id')->nullable()->after('invoice_id')
                ->constrained('fin_accounts')->nullOnDelete();

            $table->string('currency')->default('VND')->after('amount');
            $table->string('reference_no')->nullable()->after('transaction_id');
            $table->text('description')->nullable()->after('note');

            $table->unsignedBigInteger('parent_payment_id')->nullable()->after('description');

            $table->unsignedBigInteger('confirmed_by')->nullable()->after('parent_payment_id');
            $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');

            $table->unsignedBigInteger('created_by')->nullable()->after('confirmed_at');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');

            $table->softDeletes();

            $table->index('payment_type');
            $table->index('payment_date');
            $table->index('parent_payment_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('fin_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_id');
            $table->dropConstrainedForeignId('branch_id');
            $table->dropIndex(['payment_type']);
            $table->dropIndex(['payment_date']);
            $table->dropIndex(['parent_payment_id']);
            $table->dropIndex(['status']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'payment_no', 'payment_date', 'payment_type', 'currency',
                'reference_no', 'description', 'parent_payment_id',
                'confirmed_by', 'confirmed_at', 'created_by', 'updated_by', 'deleted_by',
            ]);
        });
    }
};
