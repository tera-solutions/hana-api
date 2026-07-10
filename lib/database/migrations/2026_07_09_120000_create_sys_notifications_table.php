<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('object_id')->nullable();
            $table->string('object_type')->nullable();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->string('type')->nullable();
            $table->boolean('is_view')->default(false);
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->boolean('is_delete')->default(false);
            $table->timestamps();

            $table->index('class_id');
            $table->index('object_id');
            $table->index('parent_id');
        });

        Schema::create('sys_notification_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('notification_id');
            $table->boolean('is_view')->default(false);
            $table->timestamps();

            $table->index('user_id');
            $table->index('notification_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_notification_users');
        Schema::dropIfExists('sys_notifications');
    }
};
