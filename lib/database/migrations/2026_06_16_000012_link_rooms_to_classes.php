<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wire the free-standing edu_classes.room_id column to edu_rooms (room.md §14).
 * Nullable; detaches (set null) when a room is removed.
 *
 * Per room.md §14 the room's children are classes and lessons: lessons link via
 * their own FK in create_edu_lessons. edu_sessions keeps a plain room_id column
 * (class-session.md) and is intentionally not constrained here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_classes', function (Blueprint $table) {
            $table->foreign('room_id')->references('id')->on('edu_rooms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('edu_classes', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
        });
    }
};
