<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_student_profiles', function (Blueprint $table) {
            $table->string('province')->nullable()->after('grade');
            $table->string('district')->nullable()->after('province');
        });
    }

    public function down(): void
    {
        Schema::table('edu_student_profiles', function (Blueprint $table) {
            $table->dropColumn(['province', 'district']);
        });
    }
};
