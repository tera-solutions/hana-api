<?php

namespace App\Modules\Education\PlacementTest\Actions;

use App\Modules\Education\PlacementTest\Services\PlacementTestService;

class ListPlacementTestAction
{
    public function handle(...$params)
    {
        return app(PlacementTestService::class)->paginate(...$params);
    }
}
