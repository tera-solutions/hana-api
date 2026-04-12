<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reference_counts', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();

            $table->string('ref_type');     // type entity (student, course...)
            $table->integer('ref_count');   // counter

            $table->unsignedInteger('business_id');
            $table->index('business_id');

            $table->unique(['ref_type', 'business_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_counts');
    }
};