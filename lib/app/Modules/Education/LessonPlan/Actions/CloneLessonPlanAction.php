<?php

namespace App\Modules\Education\LessonPlan\Actions;

use App\Modules\Education\LessonPlan\Services\LessonPlanService;

class CloneLessonPlanAction
{
    public function handle(...$params)
    {
        return app(LessonPlanService::class)->clone(...$params);
    }
}
