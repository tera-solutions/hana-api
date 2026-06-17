<?php

namespace App\Modules\Education\LessonPlanLesson\Actions;

use App\Modules\Education\LessonPlanLesson\Services\LessonPlanLessonService;

class ReorderLessonsAction
{
    public function handle(...$params)
    {
        return app(LessonPlanLessonService::class)->reorderLessons(...$params);
    }
}
