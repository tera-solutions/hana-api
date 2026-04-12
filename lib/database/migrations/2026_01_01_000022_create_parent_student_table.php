<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('crm_parent_student', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parent_id')->constrained('crm_parents')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('edu_students')->cascadeOnDelete();

            $table->string('relation')->nullable(); // father, mother

            $table->timestamps();

            $table->unique(['parent_id', 'student_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('crm_parent_student');
    }
};