<?php

namespace App\Modules\Education\Exam\Actions;

use App\Modules\Education\Exam\Services\ExamService;

class AddExamQuestionAction
{
    public function handle(...$params)
    {
        return app(ExamService::class)->addQuestion(...$params);
    }
}
