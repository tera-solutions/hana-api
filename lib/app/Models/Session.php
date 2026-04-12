<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    
    
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function user()
    {
        return $this->hasMany(\App\Models\User::class, 'user_id');
    }
}
