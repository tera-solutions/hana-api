<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalAccessClient extends Model
{
    
    protected $table = 'oauth_personal_access_clients';

    public function users()
    {
        return $this->belongsTo(User::class, 'id', 'user_id');
    }
}
