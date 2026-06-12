<?php

namespace App\Modules\Education\Course\Actions;

use App\Modules\Education\Course\Services\CourseService;

class SuspendCourseAction
{
    public function handle(...$params)
    {
        return app(CourseService::class)->suspend(...$params);
    }
}
