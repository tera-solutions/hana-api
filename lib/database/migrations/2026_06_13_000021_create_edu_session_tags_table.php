<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Session ↔ tag pivot (class-session.md §13, §15). One row per (session, tag).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_session_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('edu_sessions')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('crm_tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['session_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_session_tags');
    }
};
