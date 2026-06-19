<?php

namespace App\Modules\Education\Exam\Actions;

use App\Modules\Education\Exam\Services\ExamSessionService;

class CreateExamSessionAction
{
    public function handle(...$params)
    {
        return app(ExamSessionService::class)->create(...$params);
    }
}
