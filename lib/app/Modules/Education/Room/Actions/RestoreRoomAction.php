<?php

namespace App\Modules\Education\Room\Actions;

use App\Modules\Education\Room\Services\RoomService;

class RestoreRoomAction
{
    public function handle(...$params)
    {
        return app(RoomService::class)->restore(...$params);
    }
}
