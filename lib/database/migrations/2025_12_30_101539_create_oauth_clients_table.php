<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('oauth_clients', function (Blueprint $table) {
            $table->increments('id'); // int UNSIGNED NOT NULL AUTO_INCREMENT
            $table->bigInteger('user_id')->nullable()->index();
            $table->string('name', 191);
            $table->string('secret', 100);
            $table->text('redirect');
            $table->boolean('personal_access_client');
            $table->boolean('password_client');
            $table->boolean('revoked');
            $table->string('provider')->nullable()->default('users');
            $table->timestamps();
        });
        DB::table('oauth_clients')->insert([
            [
                'name' => 'Hana Personal Access Client',
                'secret' => Str::random(40),
                'redirect' => 'http://localhost',
                'personal_access_client' => 1,
                'password_client' => 0,
                'revoked' => 0,
                'provider' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hana Password Grant Client',
                'secret' => Str::random(40),
                'redirect' => 'http://localhost',
                'personal_access_client' => 0,
                'password_client' => 1,
                'revoked' => 0,
                'provider' => 'users',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_clients');
    }

    /**
     * Get the migration connection name.
     */
    public function getConnection(): ?string
    {
        return $this->connection ?? config('passport.connection');
    }
};
