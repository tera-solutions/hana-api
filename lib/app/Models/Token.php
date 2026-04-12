<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'oauth_verification_tokens';
  protected $fillable = [
    'id',
    'user_id',
    'token',
    'expired',
    'type',
    'created_at',
    'updated_at',
  ];
}
