<?php

namespace App\Modules\Education\LessonPlan\Actions;

use App\Modules\Education\LessonPlan\Services\LessonPlanService;

class ArchiveLessonPlanAction
{
    public function handle(...$params)
    {
        return app(LessonPlanService::class)->archive(...$params);
    }
}
