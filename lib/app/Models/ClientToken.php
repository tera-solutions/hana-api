<?php

namespace App\Models;

use Laravel\Passport\Client;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientToken extends Client
{
    
    protected $table = 'oauth_clients';

    protected $guarded = [];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'secret',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'personal_access_client' => 'bool',
        'password_client' => 'bool',
        'revoked' => 'bool',
    ];

    /**
     * Get all of the authentication codes for the client.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function authCodes(): HasMany
    {
        return $this->hasMany(AuthCode::class, 'client_id');
    }

    /**
     * Get all of the tokens that belong to the client.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(AccessToken::class, 'client_id');
    }

    /**
     * Determine if the client is a "first party" client.
     *
     * @return bool
     */
    public function firstParty(): bool
    {
        return $this->personal_access_client || $this->password_client;
    }

    /**
     * Determine if the client has the given grant type.
     */
    public function hasGrantType(string $grantType): bool
    {
        if ($grantType === 'client_credentials' && $this->personal_access_client) {
            return false;
        }

        return parent::hasGrantType($grantType);
    }
}
