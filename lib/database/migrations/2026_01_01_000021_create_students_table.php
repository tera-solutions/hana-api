<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('edu_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('business_id')->nullable()->constrained('sys_business')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('sys_branches')->nullOnDelete();

            $table->string('code')->unique();
            $table->string('name');
            $table->date('dob')->nullable();
            $table->string('gender')->nullable();

            $table->string('level')->nullable(); // A1, B1...
            $table->string('status')->default('studying'); // studying, stopped


            $table->index(['business_id', 'branch_id', 'status']);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_students');
    }
};