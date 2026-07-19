<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-class weighted score structure (EDU-17 "cấu hình trọng số điểm") —
 * `components` is a JSON array of {key, label, weight}, weights summing to 100.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_score_configs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')->nullable()->constrained('sys_business')->nullOnDelete();
            $table->foreignId('class_id')->unique()->constrained('edu_classes')->cascadeOnDelete();

            $table->json('components');

            $table->timestamps();
            $table->auditColumns();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_score_configs');
    }
};
