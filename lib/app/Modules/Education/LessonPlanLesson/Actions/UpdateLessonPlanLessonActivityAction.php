<?php

namespace App\Modules\Education\LessonPlanLesson\Actions;

use App\Modules\Education\LessonPlanLesson\Models\LessonPlanLessonActivity;
use App\Modules\Education\LessonPlanLesson\Services\LessonPlanLessonActivityService;

class UpdateLessonPlanLessonActivityAction
{
    public function handle($id, array $data): LessonPlanLessonActivity
    {
        return app(LessonPlanLessonActivityService::class)->update($id, $data);
    }
}
