<?php

namespace App\Modules\Education\Room\Actions;

use App\Modules\Education\Room\Services\RoomService;

class ListRoomAction
{
    public function handle(...$params)
    {
        return app(RoomService::class)->paginate(...$params);
    }
}
