<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hr_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('business_id')->nullable()->constrained('sys_business')->nullOnDelete();

            $table->string('code')->unique();
            $table->string('name');

            $table->string('type')->default('teacher');
            $table->string('status')->default('active');

            $table->decimal('salary_per_hour', 10, 2)->nullable();
            $table->index(['business_id', 'status']);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_teachers');
    }
};