<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per business — whether/how `invoices:generate-recurring` should
 * auto-bill active enrollments each month.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_invoice_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained('sys_business')->cascadeOnDelete();

            $table->boolean('auto_generate')->default(false);
            $table->unsignedTinyInteger('billing_day')->default(1);
            $table->unsignedSmallInteger('due_days')->default(7);

            $table->timestamps();
            $table->auditColumns();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_invoice_configs');
    }
};
