<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sessions (buổi học) generated for a class (spec §7 operational statistics).
 * Status drives total / completed / pending session counts. Distinct from a
 * course "lesson" (bài học), which is a curriculum unit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_id')->constrained('edu_classes')->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('edu_class_schedules')->nullOnDelete();

            $table->string('title')->nullable();
            $table->date('session_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->string('status')->default('pending'); // pending, completed, canceled

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            $table->index(['class_id', 'status']);
            $table->index(['class_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_sessions');
    }
};
