<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hr_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('hr_teachers')->cascadeOnDelete();
            $table->string('type'); // fulltime, parttime
            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->decimal('base_salary', 12, 2)->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_contracts');
    }
};