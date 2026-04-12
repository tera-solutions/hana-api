<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('edu_rooms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')
                ->constrained('sys_business')
                ->cascadeOnDelete();

            $table->string('name'); // Room A1, B2
            $table->string('code')->unique();

            $table->integer('capacity')->default(20);

            $table->string('type')->default('offline'); 

            $table->string('status')->default('active'); 

            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('edu_rooms');
    }
};