<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hr_timesheets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_id')->constrained('hr_teachers')->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('hr_teaching_schedules');

            $table->date('date');

            $table->decimal('hours', 5, 2);
            $table->string('status')->default('pending'); // approved

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_timesheets');
    }
};