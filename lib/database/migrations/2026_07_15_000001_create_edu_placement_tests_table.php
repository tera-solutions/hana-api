<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_placement_tests', function (Blueprint $table) {
            $table->id();

            $table->string('test_code')->unique();
            $table->string('title');
            $table->text('description')->nullable();

            $table->string('cefr_level')->nullable();
            $table->json('skills')->nullable();

            $table->unsignedInteger('question_count')->default(0);
            $table->unsignedInteger('duration_minutes')->default(0);

            $table->string('status')->default('draft'); // draft, published

            $table->foreignId('teacher_id')->nullable()->constrained('hr_teachers')->nullOnDelete();
            $table->foreignId('business_id')->nullable()->constrained('sys_business')->nullOnDelete();

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            $table->index(['teacher_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_placement_tests');
    }
};
