<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Created by TeraCore.
 * User: truong.nq
 * Date: 5/9/2020
 * Time: 3:20 PM
 */
class TableVersionLog extends Model
{
    public $timestamps = false;

    protected $table = 'table_version_logs';

    protected $fillable = [
        'table_name',
        'user_id',
        'business_id',
        'version',
        'updated_at',
    ];
}
