<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthCode extends Model
{
    
    protected $table = 'oauth_auth_codes';

    public function users()
    {
        return $this->belongsTo(User::class, 'id', 'user_id');
    }
}
