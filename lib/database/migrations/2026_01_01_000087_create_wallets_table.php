<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fin_wallets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')
                ->constrained('sys_business')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // số dư
            $table->decimal('balance', 12, 2)->default(0);

            // loại ví
            $table->string('type')->default('main');
            // main | bonus | refund

            // trạng thái
            $table->string('status')->default('active');

            $table->timestamps();

            $table->unique(['business_id', 'user_id', 'type']);
            $table->index(['business_id', 'user_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('fin_wallets');
    }
};