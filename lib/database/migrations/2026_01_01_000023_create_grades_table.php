<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('edu_grades', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')->nullable()->constrained('sys_business')->nullOnDelete();
            $table->foreignId('student_id')->constrained('edu_students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('edu_classes')->cascadeOnDelete();

            $table->decimal('score', 5, 2);
            $table->string('type');

            $table->index(['business_id', 'class_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_grades');
    }
};