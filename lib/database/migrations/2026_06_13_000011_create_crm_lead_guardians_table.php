<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_lead_guardians', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lead_id')->constrained('crm_leads')->cascadeOnDelete();

            $table->string('full_name');
            $table->string('relationship');
            $table->string('phone');
            $table->string('email')->nullable();

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            // Phone is unique within a lead — enforced at the application layer
            // (so it coexists with soft deletes); this index backs the lookup.
            $table->index(['lead_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_lead_guardians');
    }
};
