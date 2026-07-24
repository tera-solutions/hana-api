<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reusable certificate designs (teacher-app-076) — a business-scoped catalogue
 * of preview images to pick from when issuing a certificate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('sys_business')->cascadeOnDelete();

            $table->string('name');
            $table->string('preview_image')->nullable();
            $table->json('placeholders')->nullable();

            $table->string('status')->default('active');

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_certificate_templates');
    }
};
