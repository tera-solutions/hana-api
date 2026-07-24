<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_enrollments', function (Blueprint $table) {
            $table->foreignId('subscription_package_id')->nullable()->after('course_id')
                ->constrained('fin_subscription_packages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('edu_enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_package_id');
        });
    }
};
