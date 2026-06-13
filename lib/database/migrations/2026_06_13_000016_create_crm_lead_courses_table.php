<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_lead_courses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lead_id')->constrained('crm_leads')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('edu_courses')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['lead_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_lead_courses');
    }
};
