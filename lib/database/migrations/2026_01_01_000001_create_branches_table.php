<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sys_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('sys_business')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique(); // Mã chi nhánh (VD: CN01, CN_HCM)
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address');
            $table->string('manager_name')->nullable(); // Tên quản lý chi nhánh
            $table->boolean('is_main_branch')->default(false); // Xác định chi nhánh chính
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_branches');
    }
};