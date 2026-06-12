<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_course_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')->nullable()->constrained('sys_business')->nullOnDelete();
            $table->foreignId('course_id')->constrained('edu_courses')->cascadeOnDelete();

            $table->string('action'); // created, updated, suspended, restored
            $table->boolean('from_active')->nullable();
            $table->boolean('to_active')->nullable();

            $table->text('reason')->nullable();
            $table->text('note')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['course_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_course_histories');
    }
};
