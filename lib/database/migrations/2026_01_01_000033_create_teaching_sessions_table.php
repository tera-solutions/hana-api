<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hr_teaching_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')->nullable()->constrained('sys_business')->nullOnDelete();
            $table->foreignId('class_id')->constrained('edu_classes')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('hr_teachers')->cascadeOnDelete();

            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');

            $table->string('status')->default('scheduled'); // scheduled, done, cancelled

            $table->index(['business_id', 'class_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_teaching_sessions');
    }
};