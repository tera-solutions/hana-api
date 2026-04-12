<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('edu_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('level_id')->nullable()->constrained('edu_levels')->nullOnDelete();

            $table->string('type'); // mcq, writing
            $table->text('content');

            $table->json('options')->nullable();
            $table->text('answer')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_questions');
    }
};