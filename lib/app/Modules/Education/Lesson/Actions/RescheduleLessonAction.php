<?php

namespace App\Modules\Education\Lesson\Actions;

use App\Modules\Education\Lesson\Services\LessonService;

class RescheduleLessonAction
{
    public function handle(...$params)
    {
        return app(LessonService::class)->reschedule(...$params);
    }
}
