<?php

namespace App\Modules\Education\CourseCurriculum\Actions;

use App\Modules\Education\CourseCurriculum\Services\CourseCurriculumService;

class DeleteCourseCurriculumAction
{
    public function handle($id): void
    {
        app(CourseCurriculumService::class)->delete($id);
    }
}
