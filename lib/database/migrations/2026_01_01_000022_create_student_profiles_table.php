<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('edu_student_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained('edu_students')->cascadeOnDelete();

            $table->string('school')->nullable();
            $table->string('grade')->nullable();

            $table->text('address')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_student_profiles');
    }
};