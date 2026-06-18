<?php

namespace App\Modules\Education\Level\Actions;

use App\Modules\Education\Level\Services\LevelService;

class UpdateLevelAction
{
    public function handle(...$params)
    {
        return app(LevelService::class)->update(...$params);
    }
}
