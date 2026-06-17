<?php

namespace App\Modules\Education\LessonPlanLesson\Actions;

use App\Modules\Education\LessonPlanLesson\Services\LessonPlanLessonService;

class UpdateLessonAction
{
    public function handle(...$params)
    {
        return app(LessonPlanLessonService::class)->updateLesson(...$params);
    }
}
