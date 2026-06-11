<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->unique()->after('email');
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained('sys_branches')->nullOnDelete();
            $table->string('gender')->nullable()->after('phone');
            $table->date('dob')->nullable()->after('gender');
            $table->string('department')->nullable()->after('dob');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_ip')->nullable();
            $table->unsignedInteger('login_count')->default(0);
            $table->auditColumns();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropUnique(['phone']);
            $table->dropColumn([
                'phone',
                'gender',
                'dob',
                'department',
                'last_login_at',
                'last_ip',
                'login_count',
            ]);
            $table->dropAuditColumns();
        });
    }
};
