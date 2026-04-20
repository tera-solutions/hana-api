<?php

namespace App\Modules\Education\Course\Actions;

use App\Modules\Education\Course\Services\CourseService;

class DeleteCourseAction
{
    public function handle(...$params)
    {
        return app(CourseService::class)->delete(...$params);
    }
}