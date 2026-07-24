<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_certificates', function (Blueprint $table) {
            $table->foreignId('template_id')->nullable()->after('class_id')
                ->constrained('edu_certificate_templates')->nullOnDelete();
            $table->foreignId('course_id')->nullable()->after('template_id')
                ->constrained('edu_courses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('edu_certificates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('template_id');
            $table->dropConstrainedForeignId('course_id');
        });
    }
};
