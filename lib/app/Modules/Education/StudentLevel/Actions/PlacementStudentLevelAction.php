<?php

namespace App\Modules\Education\StudentLevel\Actions;

use App\Modules\Education\StudentLevel\Services\StudentLevelService;

class PlacementStudentLevelAction
{
    public function handle(...$params)
    {
        return app(StudentLevelService::class)->placement(...$params);
    }
}
