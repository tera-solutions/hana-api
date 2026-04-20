<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sys_roles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')
                ->constrained('sys_business')
                ->cascadeOnDelete();

            $table->string('title');
            $table->string('code')->unique();
            $table->string('type');
            $table->string('guard_name')->default('api');

            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_roles');
    }
};