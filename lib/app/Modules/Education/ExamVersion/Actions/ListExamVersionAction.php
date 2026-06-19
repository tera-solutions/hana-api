<?php

namespace App\Modules\Education\ExamVersion\Actions;

use App\Modules\Education\ExamVersion\Services\ExamVersionService;

class ListExamVersionAction
{
    public function handle(...$params)
    {
        return app(ExamVersionService::class)->listForExam(...$params);
    }
}
