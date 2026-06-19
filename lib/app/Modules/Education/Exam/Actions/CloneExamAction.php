<?php

namespace App\Modules\Education\Exam\Actions;

use App\Modules\Education\Exam\Services\ExamService;

class CloneExamAction
{
    public function handle(...$params)
    {
        return app(ExamService::class)->clone(...$params);
    }
}
