<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hr_disciplinary_actions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_id')->constrained('hr_teachers')->cascadeOnDelete();

            $table->string('type'); // warning, penalty
            $table->decimal('amount', 12, 2)->nullable();

            $table->text('reason');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_disciplinary_actions');
    }
};