<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail of invoice status transitions (created, approved, denied, cancelled,
 * refunded, paid) — invoice.md §IX approval workflow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_invoice_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained('fin_invoices')
                ->cascadeOnDelete();

            $table->string('action');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->string('reason')->nullable();
            $table->text('note')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_invoice_histories');
    }
};
