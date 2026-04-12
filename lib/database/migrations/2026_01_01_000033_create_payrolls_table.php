<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hr_payrolls', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_id')->constrained('hr_teachers')->cascadeOnDelete();
            $table->foreignId('business_id')->nullable()->constrained('sys_business')->nullOnDelete();

            $table->integer('month');
            $table->integer('year');

            $table->decimal('total_hours', 8, 2)->default(0);
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);
            $table->decimal('penalty', 12, 2)->default(0);

            $table->decimal('total_salary', 12, 2);

            $table->timestamps();

            $table->index(['business_id']);
            $table->unique(['teacher_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_payrolls');
    }
};