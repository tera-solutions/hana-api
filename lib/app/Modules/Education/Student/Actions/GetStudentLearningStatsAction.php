<?php

namespace App\Modules\Education\Student\Actions;

use App\Modules\Education\Student\Services\StudentService;

class GetStudentLearningStatsAction
{
    public function handle(...$params)
    {
        return app(StudentService::class)->learningStats(...$params);
    }
}
