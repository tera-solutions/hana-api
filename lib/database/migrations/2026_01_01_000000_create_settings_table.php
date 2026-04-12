<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sys_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')
                ->constrained('sys_business')
                ->cascadeOnDelete();

            $table->string('key');

            $table->text('value')->nullable();

            $table->string('type')->default('string');

            $table->string('group')->nullable();

            $table->string('label')->nullable();

            $table->timestamps();

            $table->unique(['business_id', 'key']);

            $table->index(['business_id', 'group']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('sys_settings');
    }
};