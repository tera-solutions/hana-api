<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained('sys_business')->nullOnDelete();

            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();

            $table->string('source')->nullable();
            $table->string('status')->default('new');


            $table->index(['business_id', 'status']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_leads');
    }
};