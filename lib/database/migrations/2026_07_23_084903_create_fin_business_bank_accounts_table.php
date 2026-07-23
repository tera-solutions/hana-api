<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The business's own bank accounts for RECEIVING tuition payments — distinct
 * from `fin_bank_accounts` (teacher/staff payout accounts). `bank_code` is the
 * VietQR bank identifier (BIN or acronym, e.g. "970422" / "MB") used to build
 * the VietQR quick-link image URL for invoice payment QR codes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_business_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('sys_business')->cascadeOnDelete();

            $table->string('bank_name');
            $table->string('bank_code');
            $table->string('account_number');
            $table->string('account_holder');
            $table->string('branch')->nullable();

            $table->boolean('is_default')->default(false);
            $table->string('status')->default('active');

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_business_bank_accounts');
    }
};
