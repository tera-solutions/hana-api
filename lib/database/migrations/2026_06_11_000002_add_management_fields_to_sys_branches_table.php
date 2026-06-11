<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sys_branches', function (Blueprint $table) {
            $table->string('short_name')->nullable()->after('name');
            $table->string('website')->nullable()->after('email');
            $table->string('province')->nullable()->after('address');
            $table->string('district')->nullable()->after('province');
            $table->string('ward')->nullable()->after('district');
            $table->string('postal_code')->nullable()->after('ward');
            $table->foreignId('manager_id')->nullable()->after('postal_code')->constrained('users')->nullOnDelete();
            $table->unsignedInteger('capacity')->nullable()->after('manager_id');
            $table->date('opened_at')->nullable()->after('capacity');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('opened_at');
            $table->dropUnique(['code']);
            $table->unique(['business_id', 'code']);
            $table->auditColumns();
        });
    }

    public function down(): void
    {
        Schema::table('sys_branches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_id');
            $table->dropUnique(['business_id', 'code']);
            $table->unique('code');
            $table->dropColumn([
                'short_name',
                'website',
                'province',
                'district',
                'ward',
                'postal_code',
                'capacity',
                'opened_at',
                'status',
            ]);
            $table->dropAuditColumns();
        });
    }
};
