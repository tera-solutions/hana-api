<?php

namespace App\Modules\Education\Course\Actions;

use App\Modules\Education\Course\Services\CourseService;

class UpdateCourseAction
{
    public function handle(...$params)
    {
        return app(CourseService::class)->update(...$params);
    }
}