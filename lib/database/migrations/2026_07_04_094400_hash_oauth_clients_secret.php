<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration {
    /**
     * Passport's ClientRepository validates client secrets with Hash::check(),
     * so they must be bcrypt hashes, not plaintext. The original
     * create_oauth_clients_table migration seeded both clients with a raw
     * Str::random(40) secret, which breaks the password grant used by
     * ApiAuthController's login/refresh-token flow. This re-hashes existing
     * rows and makes the password client's plaintext secret configurable via
     * PASSPORT_PASSWORD_CLIENT_SECRET so it stays stable across reseeds.
     */
    public function up(): void
    {
        DB::table('oauth_clients')->where('password_client', 1)->get()->each(function ($client) {
            $secret = env('PASSPORT_PASSWORD_CLIENT_SECRET');
            if ($secret && ! Hash::isHashed($client->secret)) {
                DB::table('oauth_clients')->where('id', $client->id)->update([
                    'secret' => Hash::make($secret),
                ]);
            }
        });

        DB::table('oauth_clients')->where('personal_access_client', 1)->get()->each(function ($client) {
            if (! Hash::isHashed($client->secret)) {
                DB::table('oauth_clients')->where('id', $client->id)->update([
                    'secret' => Hash::make($client->secret),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Not reversible: original plaintext secrets aren't recoverable from a hash.
    }

    public function getConnection(): ?string
    {
        return $this->connection ?? config('passport.connection');
    }
};
