<?php

namespace App\Modules\Education\Lesson\Actions;

use App\Modules\Education\Lesson\Services\LessonService;

class UnlockLessonAction
{
    public function handle(...$params)
    {
        return app(LessonService::class)->unlock(...$params);
    }
}
