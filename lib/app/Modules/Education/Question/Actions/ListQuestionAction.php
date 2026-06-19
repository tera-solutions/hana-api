<?php

namespace App\Modules\Education\Question\Actions;

use App\Modules\Education\Question\Services\QuestionService;

class ListQuestionAction
{
    public function handle(...$params)
    {
        return app(QuestionService::class)->paginate(...$params);
    }
}
