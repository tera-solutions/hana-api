<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tuition subscription packages (teacher-app-081/082) — priced by session,
 * month, term or a custom amount, applied when enrolling a student.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_subscription_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('sys_business')->cascadeOnDelete();

            $table->string('name');
            $table->string('type'); // session|month|term|custom
            $table->decimal('price', 12, 2)->nullable();
            $table->unsignedInteger('sessions_included')->nullable();
            $table->unsignedInteger('duration_days')->nullable();

            // Course ids this package can be applied to, or null = all courses.
            $table->json('applicable_courses')->nullable();

            $table->string('status')->default('active');

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_subscription_packages');
    }
};
