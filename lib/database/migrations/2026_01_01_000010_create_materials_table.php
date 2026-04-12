<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('edu_materials', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('type')->nullable(); // book, video

            $table->string('file')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_materials');
    }
};