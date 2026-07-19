<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EDU-18 Certificate — completion certificates issued to a student for a
 * class, gated on the class's finalized score (`edu_grades` type "final",
 * see `2026_07_19_000001/2` for EDU-17). `verify_token` backs the public QR
 * verification page and is looked up with no tenant scoping (unauthenticated
 * route — see `BusinessScope`, which no-ops when there is no tenant context).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_certificates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')->nullable()->constrained('sys_business')->nullOnDelete();
            $table->foreignId('student_id')->constrained('edu_students')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('edu_classes')->nullOnDelete();

            $table->string('certificate_no')->unique();
            $table->uuid('verify_token')->unique();

            $table->string('status')->default('issued'); // issued, revoked
            $table->decimal('final_score', 5, 2)->nullable();

            $table->dateTime('issued_at');
            $table->dateTime('revoked_at')->nullable();
            $table->string('revoke_reason')->nullable();

            $table->timestamps();
            $table->auditColumns();

            $table->index(['business_id', 'class_id']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_certificates');
    }
};
