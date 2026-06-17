<?php

namespace App\Modules\Education\Room\Actions;

use App\Modules\Education\Room\Services\RoomService;

class CreateRoomAction
{
    public function handle(...$params)
    {
        return app(RoomService::class)->create(...$params);
    }
}
