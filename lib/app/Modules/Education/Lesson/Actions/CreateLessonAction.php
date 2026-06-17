<?php

namespace App\Modules\Education\Lesson\Actions;

use App\Modules\Education\Lesson\Services\LessonService;

class CreateLessonAction
{
    public function handle(...$params)
    {
        return app(LessonService::class)->create(...$params);
    }
}
