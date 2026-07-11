<?php

namespace App\Modules\Education\CourseCurriculum\Actions;

use App\Modules\Education\Course\Models\CourseCurriculum;
use App\Modules\Education\CourseCurriculum\Services\CourseCurriculumService;

class GetCourseCurriculumAction
{
    public function handle($id): CourseCurriculum
    {
        return app(CourseCurriculumService::class)->find($id);
    }
}
