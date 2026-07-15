<?php

namespace App\Modules\Education\CourseCurriculum\Actions;

use App\Modules\Education\Course\Models\CourseCurriculum;
use App\Modules\Education\CourseCurriculum\Services\CourseCurriculumService;

class UpdateCourseCurriculumAction
{
    public function handle($id, array $data): CourseCurriculum
    {
        return app(CourseCurriculumService::class)->update($id, $data);
    }
}
