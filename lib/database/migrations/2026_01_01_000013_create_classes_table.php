<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('edu_classes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_id')->constrained('edu_courses')->cascadeOnDelete();
            $table->foreignId('business_id')->nullable()->constrained('sys_business')->nullOnDelete();

            $table->string('name');
            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->integer('max_students')->default(20);

            $table->string('status')->default('opening'); // opening, running, closed

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_classes');
    }
};