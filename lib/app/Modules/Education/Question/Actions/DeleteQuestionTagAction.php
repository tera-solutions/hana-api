<?php

namespace App\Modules\Education\Question\Actions;

use App\Modules\Education\Question\Services\QuestionTagService;

class DeleteQuestionTagAction
{
    public function handle(...$params)
    {
        return app(QuestionTagService::class)->delete(...$params);
    }
}
