<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_subscription_package_discount_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('fin_subscription_packages')->cascadeOnDelete();

            $table->string('type'); // multi_term|sibling|code
            $table->decimal('value', 5, 2);
            $table->string('condition')->nullable();
            $table->boolean('enabled')->default(true);

            $table->timestamps();

            $table->index(['package_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_subscription_package_discount_rules');
    }
};
