<?php

namespace App\Modules\Education\PlacementTest\Actions;

use App\Modules\Education\PlacementTest\Services\PlacementTestService;

class RecordPlacementTestResultAction
{
    public function handle(...$params)
    {
        return app(PlacementTestService::class)->recordResult(...$params);
    }
}
