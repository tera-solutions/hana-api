<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferenceCount extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */

    protected $table = 'sys_reference_counts';

    protected $guarded = ['id'];
}
