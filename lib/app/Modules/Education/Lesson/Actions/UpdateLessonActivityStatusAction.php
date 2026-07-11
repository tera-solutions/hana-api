<?php

namespace App\Modules\Education\Lesson\Actions;

use App\Modules\Education\Lesson\Services\LessonService;

class UpdateLessonActivityStatusAction
{
    public function handle(...$params)
    {
        return app(LessonService::class)->updateActivityStatus(...$params);
    }
}
