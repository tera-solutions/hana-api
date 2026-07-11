<?php

namespace App\Modules\Education\LessonPlanLesson\Actions;

use App\Modules\Education\LessonPlanLesson\Models\LessonPlanLessonActivity;
use App\Modules\Education\LessonPlanLesson\Services\LessonPlanLessonActivityService;

class CreateLessonPlanLessonActivityAction
{
    public function handle(array $data): LessonPlanLessonActivity
    {
        return app(LessonPlanLessonActivityService::class)->create($data);
    }
}
