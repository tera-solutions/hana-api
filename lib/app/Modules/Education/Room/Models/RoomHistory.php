<?php

namespace App\Modules\Education\Room\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomHistory extends Model
{
    protected $table = 'edu_room_histories';

    protected $guarded = [];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
}
