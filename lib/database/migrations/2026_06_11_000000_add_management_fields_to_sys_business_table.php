<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sys_business', function (Blueprint $table) {
            $table->string('business_code')->nullable()->unique()->after('id');
            $table->string('short_name')->nullable()->after('name');
            $table->string('prefix')->nullable()->after('short_name');
            $table->string('province')->nullable()->after('address');
            $table->string('district')->nullable()->after('province');
            $table->string('ward')->nullable()->after('district');
            $table->string('zip_code')->nullable()->after('ward');
            $table->foreignId('manager_id')->nullable()->after('zip_code')->constrained('users')->nullOnDelete();
            $table->index('business_code');
            $table->index('status');
            $table->auditColumns();
        });
    }

    public function down(): void
    {
        Schema::table('sys_business', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_id');
            $table->dropIndex(['business_code']);
            $table->dropIndex(['status']);
            $table->dropUnique(['business_code']);
            $table->dropColumn([
                'business_code',
                'short_name',
                'prefix',
                'province',
                'district',
                'ward',
                'zip_code',
            ]);
            $table->dropAuditColumns();
        });
    }
};
