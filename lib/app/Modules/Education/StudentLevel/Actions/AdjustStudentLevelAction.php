<?php

namespace App\Modules\Education\StudentLevel\Actions;

use App\Modules\Education\StudentLevel\Services\StudentLevelService;

class AdjustStudentLevelAction
{
    public function handle(...$params)
    {
        return app(StudentLevelService::class)->adjust(...$params);
    }
}
