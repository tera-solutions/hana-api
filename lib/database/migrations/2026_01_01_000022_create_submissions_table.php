<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('crm_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('crm_assignments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('edu_students')->cascadeOnDelete();

            $table->text('content')->nullable();
            $table->string('file')->nullable();
            $table->decimal('score', 5, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('crm_submissions');
    }
};