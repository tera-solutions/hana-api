<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * System-wide audit log (spec 028 §X). Append-only / immutable (BR-02, BR-03):
 * only created_at, no updates or deletes. Postgres DDL in the spec translated to
 * MySQL — UUID user/role ids become unsignedBigInteger (this project uses integer
 * user ids), JSONB becomes json, BIGSERIAL becomes bigIncrements.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_activity_logs', function (Blueprint $table) {
            $table->id();

            // Business context (spec §VI).
            $table->string('module', 100)->nullable();
            $table->string('entity', 100)->nullable();
            $table->string('entity_id', 255)->nullable();
            $table->string('action', 50)->nullable();

            // Actor.
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('role_id')->nullable();

            // Request context.
            $table->string('ip_address', 100)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('request_id', 255)->nullable();
            $table->string('endpoint', 500)->nullable();
            $table->string('method', 20)->nullable();

            // Change payload (sensitive fields masked on write — BR-04).
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->json('changed_fields')->nullable();

            $table->text('description')->nullable();
            $table->string('status', 50)->nullable();

            // Immutable: created only, never updated (BR-02).
            $table->timestamp('created_at')->nullable();

            $table->index(['module', 'action']);
            $table->index(['entity', 'entity_id']);
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_activity_logs');
    }
};
