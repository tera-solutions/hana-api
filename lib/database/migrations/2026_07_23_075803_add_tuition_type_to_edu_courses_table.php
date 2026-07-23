<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('edu_courses', function (Blueprint $table) {
            $table->string('tuition_type')->default('per_lesson')->after('price_per_lesson');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('edu_courses', function (Blueprint $table) {
            $table->dropColumn('tuition_type');
        });
    }
};
