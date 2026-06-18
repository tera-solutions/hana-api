<?php

namespace App\Modules\Education\Level\Actions;

use App\Modules\Education\Level\Services\LevelService;

class RestoreLevelAction
{
    public function handle(...$params)
    {
        return app(LevelService::class)->restore(...$params);
    }
}
