<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_lead_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')->nullable()->constrained('sys_business')->nullOnDelete();
            $table->foreignId('lead_id')->constrained('crm_leads')->cascadeOnDelete();

            $table->string('action'); // created, updated, suspended, restored, owner_changed, status_changed
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();

            // For owner-change history (lead.md §4).
            $table->unsignedBigInteger('from_owner_id')->nullable();
            $table->unsignedBigInteger('to_owner_id')->nullable();

            $table->text('reason')->nullable();
            $table->text('note')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['lead_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_lead_histories');
    }
};
