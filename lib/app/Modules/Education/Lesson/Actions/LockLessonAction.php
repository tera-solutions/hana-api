<?php

namespace App\Modules\Education\Lesson\Actions;

use App\Modules\Education\Lesson\Services\LessonService;

class LockLessonAction
{
    public function handle(...$params)
    {
        return app(LessonService::class)->lock(...$params);
    }
}
