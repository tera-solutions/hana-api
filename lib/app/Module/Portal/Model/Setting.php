<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setting extends Model
{
  protected $table = "sys_settings";

  protected $fillable = [
    'key',
    'value',
    'description',
    'created_at',
    'updated_at',
  ];
}
