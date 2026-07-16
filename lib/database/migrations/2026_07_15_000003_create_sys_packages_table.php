<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_packages', function (Blueprint $table) {
            $table->id();

            $table->string('package_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();

            $table->decimal('price', 12, 2)->default(0);
            $table->string('billing_cycle')->default('month'); // month, year

            $table->json('features')->nullable();
            // Structured quota caps keyed by resource (students, classes,
            // teachers, ...). A missing/null value means unlimited.
            $table->json('limits')->nullable();
            $table->string('badge')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_packages');
    }
};
