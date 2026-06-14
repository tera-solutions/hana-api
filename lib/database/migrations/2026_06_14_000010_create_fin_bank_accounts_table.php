<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Polymorphic bank account (Tài khoản ngân hàng) shared by HR entities — teachers
 * now, staff later. One account per owner (unique owner_type + owner_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_bank_accounts', function (Blueprint $table) {
            $table->id();

            $table->string('owner_type');
            $table->unsignedBigInteger('owner_id');

            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_holder')->nullable();
            $table->string('bank_branch')->nullable();

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            $table->unique(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_bank_accounts');
    }
};
