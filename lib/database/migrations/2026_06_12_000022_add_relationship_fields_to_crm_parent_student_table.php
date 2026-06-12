<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_parent_student', function (Blueprint $table) {
            $table->boolean('is_primary_contact')->default(false)->after('relation');
            $table->boolean('is_billing_contact')->default(false)->after('is_primary_contact');
            $table->boolean('is_pickup_authorized')->default(false)->after('is_billing_contact');
            $table->text('note')->nullable()->after('is_pickup_authorized');

            // Uniqueness is now (parent, student, relation) and is enforced at the
            // application layer so it can coexist with soft deletes. The composite
            // unique also backs the parent_id FK on MySQL, so add a standalone
            // parent_id index first, otherwise dropping the unique is rejected.
            $table->index('parent_id');
            $table->dropUnique(['parent_id', 'student_id']);
            $table->index(['student_id', 'is_primary_contact']);

            $table->auditColumns();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('crm_parent_student', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropAuditColumns();
            $table->dropIndex(['student_id', 'is_primary_contact']);
            $table->unique(['parent_id', 'student_id']);
            $table->dropIndex(['parent_id']);
            $table->dropColumn([
                'is_primary_contact',
                'is_billing_contact',
                'is_pickup_authorized',
                'note',
            ]);
        });
    }
};
