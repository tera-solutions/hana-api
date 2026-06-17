<?php

namespace App\Modules\Education\Room\Actions;

use App\Modules\Education\Room\Services\RoomService;

class SuspendRoomAction
{
    public function handle(...$params)
    {
        return app(RoomService::class)->suspend(...$params);
    }
}
