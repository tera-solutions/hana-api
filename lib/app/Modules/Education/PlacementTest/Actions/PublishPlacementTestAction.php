<?php

namespace App\Modules\Education\PlacementTest\Actions;

use App\Modules\Education\PlacementTest\Services\PlacementTestService;

class PublishPlacementTestAction
{
    public function handle(...$params)
    {
        return app(PlacementTestService::class)->publish(...$params);
    }
}
