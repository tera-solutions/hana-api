<?php

namespace App\Modules\Education\LessonPlanLesson\Actions;

use App\Modules\Education\LessonPlanLesson\Services\LessonPlanLessonActivityService;

class DeleteLessonPlanLessonActivityAction
{
    public function handle($id): void
    {
        app(LessonPlanLessonActivityService::class)->delete($id);
    }
}
