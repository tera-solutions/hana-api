<?php

namespace App\Modules\Education\PlacementTest\Actions;

use App\Modules\Education\PlacementTest\Services\PlacementTestService;

class CreatePlacementTestAction
{
    public function handle(...$params)
    {
        return app(PlacementTestService::class)->create(...$params);
    }
}
