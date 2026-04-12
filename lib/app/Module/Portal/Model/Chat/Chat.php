<?php

namespace App\Module\Portal\Model\Chat;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\SellingPriceGroup;
use App\Variation;

class Chat extends Model
{
    protected $table = "chats";
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function media()
    {
        return $this->belongsTo(\App\Models\Media::class, 'media_id');
    }

    public function parent()
    {
        return $this->belongsTo(\App\Module\Portal\Model\Chat\Chat::class, 'parent_id');
    }
}
