<?php

namespace App\Modules\Education\Exam\Actions;

use App\Modules\Education\Exam\Services\ExamSessionService;

class DeleteExamSessionAction
{
    public function handle(...$params)
    {
        return app(ExamSessionService::class)->delete(...$params);
    }
}
