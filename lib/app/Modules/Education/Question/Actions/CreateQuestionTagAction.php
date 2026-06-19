<?php

namespace App\Modules\Education\Question\Actions;

use App\Modules\Education\Question\Services\QuestionTagService;

class CreateQuestionTagAction
{
    public function handle(...$params)
    {
        return app(QuestionTagService::class)->create(...$params);
    }
}
