<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_room_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_id')->constrained('edu_rooms')->cascadeOnDelete();

            $table->string('action'); // created, updated, suspended, restored
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();

            $table->text('reason')->nullable();
            $table->text('note')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['room_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_room_histories');
    }
};
