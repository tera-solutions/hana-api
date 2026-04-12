<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_placement_tests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->nullable()->constrained('edu_students')->nullOnDelete();

            $table->decimal('score', 5, 2)->nullable();
            $table->string('level_result')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_placement_tests');
    }
};