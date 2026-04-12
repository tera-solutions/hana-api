<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('edu_courses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('program_id')
                ->constrained('edu_programs')
                ->onDelete('cascade');
            $table->foreignId('level_id')
                ->constrained('edu_levels')
                ->onDelete('cascade');
            $table->foreignId('business_id')->nullable()->constrained('sys_business')->nullOnDelete();

            $table->string('name');
            $table->string('code')->unique();

            $table->integer('duration'); // hours
            $table->decimal('price', 12, 2);

            $table->index(['business_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_courses');
    }
};