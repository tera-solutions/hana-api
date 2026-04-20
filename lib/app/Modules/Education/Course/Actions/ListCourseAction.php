<?php

namespace App\Modules\Education\Course\Actions;

use App\Modules\Education\Course\Services\CourseService;

class ListCourseAction
{
    public function handle(...$params)
    {
        return app(CourseService::class)->paginate(...$params);
    }
}