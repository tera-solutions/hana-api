<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_students', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('name');
            $table->string('nationality')->nullable()->after('gender');
            $table->string('language')->nullable()->after('nationality');
            $table->string('email')->nullable()->after('language');
            $table->string('phone')->nullable()->after('email');
            $table->date('enrollment_date')->nullable()->after('status');
            $table->string('admission_source')->nullable()->after('enrollment_date');
            $table->auditColumns();
        });
    }

    public function down(): void
    {
        Schema::table('edu_students', function (Blueprint $table) {
            $table->dropAuditColumns();
            $table->dropColumn([
                'avatar',
                'nationality',
                'language',
                'email',
                'phone',
                'enrollment_date',
                'admission_source',
            ]);
        });
    }
};
