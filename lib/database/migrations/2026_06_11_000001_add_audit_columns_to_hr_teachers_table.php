<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_teachers', function (Blueprint $table) {
            $table->auditColumns();
        });
    }

    public function down(): void
    {
        Schema::table('hr_teachers', function (Blueprint $table) {
            $table->dropAuditColumns();
        });
    }
};
