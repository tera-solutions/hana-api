<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('edu_placement_test_question', function (Blueprint $table) {
            $table->id();

            $table->foreignId('placement_test_id')->constrained('edu_placement_tests')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('edu_questions')->cascadeOnDelete();

            $table->integer('order')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_placement_test_question');
    }
};
