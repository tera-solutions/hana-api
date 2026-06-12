<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_parent_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')->nullable()->constrained('sys_business')->nullOnDelete();
            $table->foreignId('parent_id')->constrained('crm_parents')->cascadeOnDelete();

            $table->string('action'); // created, updated, suspended, restored
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();

            $table->text('reason')->nullable();
            $table->text('note')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['parent_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_parent_histories');
    }
};
