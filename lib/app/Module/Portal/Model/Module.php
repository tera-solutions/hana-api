<?php

namespace App\Module\Portal\Model;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class Module extends Model
{
  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */

  protected $connection = 'admin';
  protected $table = 'ad_modules';
  protected $fillable = [
    'code',
    'title',
    'sub_title',
    'icon',
    'icon_image',
    "url",
    'type',
    'path',
    'class_name',
    'color',
    'group',
    'datasource',
    'description',
    'status',
    'created_by',
  ];

  protected $casts = ['type' => 'array'];

  public function created_by()
  {
    return $this->belongsTo(\App\Models\User::class, 'created_by');
  }
}
