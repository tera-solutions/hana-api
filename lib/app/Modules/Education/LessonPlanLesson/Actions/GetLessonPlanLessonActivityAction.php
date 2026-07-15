<?php

namespace App\Modules\Education\LessonPlanLesson\Actions;

use App\Modules\Education\LessonPlanLesson\Models\LessonPlanLessonActivity;
use App\Modules\Education\LessonPlanLesson\Services\LessonPlanLessonActivityService;

class GetLessonPlanLessonActivityAction
{
    public function handle($id): LessonPlanLessonActivity
    {
        return app(LessonPlanLessonActivityService::class)->find($id);
    }
}
