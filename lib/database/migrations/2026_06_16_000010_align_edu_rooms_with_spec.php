<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Align the legacy edu_rooms table with room.md (§13). Adds the branch scope,
 * floor, audit columns and renames the bare name/code/type/note columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        // BR001 moves uniqueness from global code to (branch, code).
        Schema::table('edu_rooms', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        Schema::table('edu_rooms', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained('sys_branches')->nullOnDelete();
            $table->string('floor')->nullable()->after('capacity');
            $table->auditColumns();
        });

        // Align column names with room.md §4.
        Schema::table('edu_rooms', function (Blueprint $table) {
            $table->renameColumn('name', 'room_name');
            $table->renameColumn('code', 'room_code');
            $table->renameColumn('type', 'room_type');
            $table->renameColumn('note', 'description');
        });

        Schema::table('edu_rooms', function (Blueprint $table) {
            $table->unique(['branch_id', 'room_code']);
            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('edu_rooms', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'room_code']);
            $table->dropIndex(['branch_id', 'status']);
        });

        Schema::table('edu_rooms', function (Blueprint $table) {
            $table->renameColumn('room_name', 'name');
            $table->renameColumn('room_code', 'code');
            $table->renameColumn('room_type', 'type');
            $table->renameColumn('description', 'note');
        });

        Schema::table('edu_rooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn('floor');
            $table->dropAuditColumns();
            $table->unique(['code']);
        });
    }
};
