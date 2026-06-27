<?php

namespace App\Modules\Education\LessonPlan\Actions;

use App\Modules\Education\LessonPlan\Services\LessonPlanService;

class SummaryLessonPlanAction
{
    public function handle(array $params = []): array
    {
        return app(LessonPlanService::class)->summary($params);
    }
}
