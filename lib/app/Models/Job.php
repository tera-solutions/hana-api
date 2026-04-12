<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Created by TeraCore.
 * User: truong.nq
 * Date: 5/9/2020
 * Time: 3:20 PM
 */
class Job extends Model
{
    public $timestamps = false;

    protected $table = 'jobs';

    protected $fillable = [
        'queue',
        'domain',
        'payload',
        'attempts',
        'business_id',
        'user_id',
        'reserved_at',
        'available_at',
        'created_at',
    ];
}
