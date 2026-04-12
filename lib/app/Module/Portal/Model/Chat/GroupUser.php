<?php

namespace App\Module\Portal\Model\Chat;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\SellingPriceGroup;
use App\Variation;

class GroupUser extends Model
{
    protected $table = "group_users";
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function group()
    {
        return $this->belongsTo(\App\Module\Portal\Model\Chat\GroupChat::class, 'group_id');
    }
}
