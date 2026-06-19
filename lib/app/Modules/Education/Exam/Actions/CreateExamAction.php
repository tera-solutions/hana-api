<?php

namespace App\Modules\Education\Exam\Actions;

use App\Modules\Education\Exam\Services\ExamService;

class CreateExamAction
{
    public function handle(...$params)
    {
        return app(ExamService::class)->create(...$params);
    }
}
