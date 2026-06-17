<?php

namespace App\Modules\Education\Room\Actions;

use App\Modules\Education\Room\Services\RoomService;

class GetRoomAction
{
    public function handle(...$params)
    {
        return app(RoomService::class)->detail(...$params);
    }
}
