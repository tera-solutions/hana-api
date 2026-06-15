<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payments gain a direction (IN for receivable receipts, OUT for payable
 * disbursements — invoice.md §XI) and an optional partner, mirroring the invoice.
 * student_id becomes optional so payable disbursements can be recorded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fin_payments', function (Blueprint $table) {
            $table->string('payment_direction')->default('in')->after('invoice_id');
            $table->string('partner_type')->nullable()->after('payment_direction');
            $table->unsignedBigInteger('partner_id')->nullable()->after('partner_type');
            $table->text('note')->nullable()->after('transaction_id');

            $table->index('payment_direction');
            $table->index(['partner_type', 'partner_id']);
        });

        Schema::table('fin_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('fin_payments', function (Blueprint $table) {
            $table->dropIndex(['payment_direction']);
            $table->dropIndex(['partner_type', 'partner_id']);
            $table->dropColumn(['payment_direction', 'partner_type', 'partner_id', 'note']);
        });

        Schema::table('fin_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id')->nullable(false)->change();
        });
    }
};
