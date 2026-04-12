<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hr_kpis', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_id')->constrained('hr_teachers')->cascadeOnDelete();
            $table->foreignId('business_id')->nullable()->constrained('sys_business')->nullOnDelete();

            $table->integer('month');
            $table->integer('year');

            $table->decimal('score', 5, 2);
            $table->decimal('bonus', 12, 2)->default(0);

            $table->text('note')->nullable();
            
            $table->index(['business_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_kpis');
    }
};