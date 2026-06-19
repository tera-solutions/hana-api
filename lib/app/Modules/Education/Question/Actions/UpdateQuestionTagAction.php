<?php

namespace App\Modules\Education\Question\Actions;

use App\Modules\Education\Question\Services\QuestionTagService;

class UpdateQuestionTagAction
{
    public function handle(...$params)
    {
        return app(QuestionTagService::class)->update(...$params);
    }
}
