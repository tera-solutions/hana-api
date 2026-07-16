<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The `limits` column was added to the create_sys_packages migration after it had
 * already run on existing databases, so those never received it. This backfills
 * the column idempotently. Structured quota caps keyed by resource
 * (students, classes, teachers, ...); a missing/null value means unlimited.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('sys_packages', 'limits')) {
            return;
        }

        Schema::table('sys_packages', function (Blueprint $table) {
            $table->json('limits')->nullable()->after('features');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('sys_packages', 'limits')) {
            return;
        }

        Schema::table('sys_packages', function (Blueprint $table) {
            $table->dropColumn('limits');
        });
    }
};
