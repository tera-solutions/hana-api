<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_parent_feedbacks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parent_id')->constrained('crm_parents')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('hr_teachers')->cascadeOnDelete();

            $table->integer('rating')->nullable();
            $table->text('content')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_parent_feedbacks');
    }
};