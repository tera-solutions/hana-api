<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_lesson_plans', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('plan_name');
        });
    }

    public function down(): void
    {
        Schema::table('edu_lesson_plans', function (Blueprint $table) {
            $table->dropColumn('avatar');
        });
    }
};
