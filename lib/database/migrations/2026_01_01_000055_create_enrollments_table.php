<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('edu_enrollments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')->nullable()->constrained('sys_business')->nullOnDelete();
            $table->foreignId('student_id')->constrained('edu_students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('edu_classes')->cascadeOnDelete();

            $table->date('enrolled_at');

            $table->string('status')->default('active'); // active, completed, dropped
            $table->decimal('progress', 5, 2)->default(0); // %

            $table->timestamps();

            $table->index(['business_id', 'class_id']);
            $table->unique(['student_id', 'class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_enrollments');
    }
};