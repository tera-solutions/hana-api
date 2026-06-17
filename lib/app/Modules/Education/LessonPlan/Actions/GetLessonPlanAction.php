<?php

namespace App\Modules\Education\LessonPlan\Actions;

use App\Modules\Education\LessonPlan\Services\LessonPlanService;

class GetLessonPlanAction
{
    public function handle(...$params)
    {
        return app(LessonPlanService::class)->detail(...$params);
    }
}
