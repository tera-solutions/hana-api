<?php

namespace App\Modules\Education\Exam\Actions;

use App\Modules\Education\Exam\Services\ExamSessionService;

class UpdateExamSessionAction
{
    public function handle(...$params)
    {
        return app(ExamSessionService::class)->update(...$params);
    }
}
