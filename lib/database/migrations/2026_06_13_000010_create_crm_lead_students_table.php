<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_lead_students', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lead_id')->constrained('crm_leads')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('edu_students')->cascadeOnDelete();

            // How the student relates to the lead (father, mother, guardian...).
            $table->string('relationship')->nullable();

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            // A lead links a student at most once. Enforced at the application
            // layer too (so it can coexist with soft deletes); this index speeds
            // up the lookups behind it.
            $table->index(['lead_id', 'student_id']);
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_lead_students');
    }
};
