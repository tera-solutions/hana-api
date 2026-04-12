<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
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
    
    protected $table = 'roles';
    protected $guarded = ['id'];

    public function created_by()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
