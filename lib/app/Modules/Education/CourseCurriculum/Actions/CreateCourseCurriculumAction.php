<?php

namespace App\Modules\Education\CourseCurriculum\Actions;

use App\Modules\Education\Course\Models\CourseCurriculum;
use App\Modules\Education\CourseCurriculum\Services\CourseCurriculumService;

class CreateCourseCurriculumAction
{
    public function handle(array $data): CourseCurriculum
    {
        return app(CourseCurriculumService::class)->create($data);
    }
}
