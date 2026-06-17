<?php

namespace App\Modules\Education\Lesson\Actions;

use App\Modules\Education\Lesson\Services\LessonService;

class GenerateLessonAction
{
    public function handle(...$params)
    {
        return app(LessonService::class)->generate(...$params);
    }
}
