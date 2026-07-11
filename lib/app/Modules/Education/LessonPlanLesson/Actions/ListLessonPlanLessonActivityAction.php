<?php

namespace App\Modules\Education\LessonPlanLesson\Actions;

use App\Modules\Education\LessonPlanLesson\Services\LessonPlanLessonActivityService;

class ListLessonPlanLessonActivityAction
{
    public function handle(array $params)
    {
        return app(LessonPlanLessonActivityService::class)->paginate($params);
    }
}
