<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class-transfer history for an enrollment (enrollment.md §10 / §14).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_enrollment_transfers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enrollment_id')->constrained('edu_enrollments')->cascadeOnDelete();
            $table->foreignId('from_class_id')->nullable()->constrained('edu_classes')->nullOnDelete();
            $table->foreignId('to_class_id')->constrained('edu_classes')->cascadeOnDelete();

            $table->date('transfer_date');
            $table->text('reason')->nullable();

            $table->timestamps();
            $table->auditColumns();

            $table->index('enrollment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_enrollment_transfers');
    }
};
