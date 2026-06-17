<?php

namespace App\Modules\Education\LessonPlanVersion\Actions;

use App\Modules\Education\LessonPlanVersion\Services\LessonPlanVersionService;

class GetLessonPlanVersionAction
{
    public function handle(...$params)
    {
        return app(LessonPlanVersionService::class)->find(...$params);
    }
}
