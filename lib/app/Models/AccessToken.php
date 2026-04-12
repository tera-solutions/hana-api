<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\Token as PassportToken;

class AccessToken extends PassportToken
{
    
    protected $table = 'oauth_access_tokens';
}
