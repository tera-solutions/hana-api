<?php

namespace App\Modules\Education\Exam\Actions;

use App\Modules\Education\Exam\Services\ExamSessionService;

class RegisterByStudentAction
{
    public function handle(...$params)
    {
        return app(ExamSessionService::class)->registerByStudent(...$params);
    }
}
