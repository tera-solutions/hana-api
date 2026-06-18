<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reshape the legacy edu_materials stub (name/type/file) into the spec-020 library
 * model (material.md §5, §14). category_id is a plain reference (no DB FK) to keep
 * the alter portable; existence is validated at the request layer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_materials', function (Blueprint $table) {
            $table->dropColumn(['name', 'type', 'file']);
        });

        Schema::table('edu_materials', function (Blueprint $table) {
            $table->string('material_code')->after('id');
            $table->string('material_name')->after('material_code');
            $table->string('material_type')->after('material_name');
            $table->unsignedBigInteger('category_id')->nullable()->after('material_type');
            $table->text('description')->nullable()->after('category_id');
            $table->integer('current_version')->default(0)->after('description');
            $table->string('access_type')->default('internal')->after('current_version');
            $table->string('status')->default('draft')->after('access_type');
            $table->auditColumns();
            $table->softDeletes();
        });

        Schema::table('edu_materials', function (Blueprint $table) {
            $table->unique('material_code');
            $table->index(['material_type', 'status']);
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('edu_materials', function (Blueprint $table) {
            $table->dropUnique(['material_code']);
            $table->dropIndex(['material_type', 'status']);
            $table->dropIndex(['category_id']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'material_code', 'material_name', 'material_type', 'category_id',
                'description', 'current_version', 'access_type', 'status',
                'created_by', 'updated_by', 'deleted_by',
            ]);
        });

        Schema::table('edu_materials', function (Blueprint $table) {
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('file')->nullable();
        });
    }
};
