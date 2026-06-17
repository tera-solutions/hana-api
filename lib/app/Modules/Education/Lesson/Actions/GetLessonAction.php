<?php

namespace App\Modules\Education\Lesson\Actions;

use App\Modules\Education\Lesson\Services\LessonService;

class GetLessonAction
{
    public function handle(...$params)
    {
        return app(LessonService::class)->detail(...$params);
    }
}
