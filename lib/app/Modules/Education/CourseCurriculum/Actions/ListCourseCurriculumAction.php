<?php

namespace App\Modules\Education\CourseCurriculum\Actions;

use App\Modules\Education\CourseCurriculum\Services\CourseCurriculumService;

class ListCourseCurriculumAction
{
    public function handle(array $params)
    {
        return app(CourseCurriculumService::class)->paginate($params);
    }
}
