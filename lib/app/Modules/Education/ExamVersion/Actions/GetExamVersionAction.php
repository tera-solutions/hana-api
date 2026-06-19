<?php

namespace App\Modules\Education\ExamVersion\Actions;

use App\Modules\Education\ExamVersion\Services\ExamVersionService;

class GetExamVersionAction
{
    public function handle(...$params)
    {
        return app(ExamVersionService::class)->find(...$params);
    }
}
