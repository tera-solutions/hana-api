<?php

namespace App\Module\Portal\Model;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DataType extends Model
{
  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */

  protected $connection = 'admin';
  protected $table = 'ad_data_types';
}
