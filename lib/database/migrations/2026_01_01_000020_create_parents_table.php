<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('crm_parents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('business_id')->nullable()->constrained('sys_business')->nullOnDelete();
            
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->index(['business_id']);

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('crm_parents');
    }
};