<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fin_invoice_configs', function (Blueprint $table) {
            $table->boolean('late_fee_enabled')->default(false)->after('due_days');
            $table->decimal('late_fee_percent', 5, 2)->nullable()->after('late_fee_enabled');
            $table->string('unpaid_student_status')->nullable()->after('late_fee_percent');
            $table->unsignedInteger('reminder_before_due_days')->nullable()->after('unpaid_student_status');
            $table->boolean('reminder_on_overdue')->default(false)->after('reminder_before_due_days');
            $table->json('reminder_channels')->nullable()->after('reminder_on_overdue');
        });
    }

    public function down(): void
    {
        Schema::table('fin_invoice_configs', function (Blueprint $table) {
            $table->dropColumn([
                'late_fee_enabled',
                'late_fee_percent',
                'unpaid_student_status',
                'reminder_before_due_days',
                'reminder_on_overdue',
                'reminder_channels',
            ]);
        });
    }
};
