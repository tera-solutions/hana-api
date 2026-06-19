<?php

namespace App\Modules\Education\QuestionVersion\Actions;

use App\Modules\Education\QuestionVersion\Services\QuestionVersionService;

class GetQuestionVersionAction
{
    public function handle(...$params)
    {
        return app(QuestionVersionService::class)->find(...$params);
    }
}
