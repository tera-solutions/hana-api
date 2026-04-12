<?php

namespace App\Module\Portal\Model;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class GroupRole extends Model
{
  
  protected $table = 'roles';
  protected $fillable = [
    'title',
    'note',
    'code',
    'type',
    'code_guard',
    'created_by',
    'updated_by',
    'is_default',
    'business_id'
  ];
  public function user_created()
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function user_updated()
  {
    return $this->belongsTo(User::class, 'updated_by');
  }
}
