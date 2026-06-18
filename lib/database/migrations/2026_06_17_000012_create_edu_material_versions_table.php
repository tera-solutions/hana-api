<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Material versions (material.md §8, §14). Each upload is a new immutable version
 * (BR004/BR005); current_version on edu_materials points at the live one, and a
 * rollback (BR006) just re-points it. file_id references the stored file — there
 * is no file-storage table in the schema yet, so metadata is kept inline.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_material_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('material_id')->constrained('edu_materials')->cascadeOnDelete();

            $table->integer('version');
            $table->unsignedBigInteger('file_id')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->text('change_log')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['material_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_material_versions');
    }
};
