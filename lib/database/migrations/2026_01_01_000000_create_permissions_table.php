<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();

            $table->string('name');              // view student, create course...
            $table->string('code')->unique();    // VIEW_STUDENT
            $table->string('guard_name')->default('api');

            $table->string('module')->nullable();   // Education, User...
            $table->string('feature')->nullable();  // Student, Course...

            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // optional index
            $table->index(['module', 'feature']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};