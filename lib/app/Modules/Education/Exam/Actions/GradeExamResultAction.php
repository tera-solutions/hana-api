<?php

namespace App\Modules\Education\Exam\Actions;

use App\Modules\Education\Exam\Services\ExamResultService;

class GradeExamResultAction
{
    public function handle(...$params)
    {
        return app(ExamResultService::class)->grade(...$params);
    }
}
