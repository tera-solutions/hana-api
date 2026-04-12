<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_reward_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained('edu_students')->cascadeOnDelete();

            $table->integer('points');
            $table->string('type'); // earn, redeem

            $table->string('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_reward_transactions');
    }
};