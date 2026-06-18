<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy uploads table (App\Models\Media / File\AjaxController@upload).
 * Columns inferred from the model fillable + the upload insert; reconcile with the
 * master clone's real `media` definition if it carries extra columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();

            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            $table->string('object_type')->nullable();
            $table->string('object_id')->nullable();

            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('type')->nullable();

            $table->string('link_file')->nullable();
            $table->unsignedBigInteger('media_id')->nullable();

            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->index(['object_type', 'object_id']);
            $table->index('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
