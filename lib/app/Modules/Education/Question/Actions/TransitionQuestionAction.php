<?php

namespace App\Modules\Education\Question\Actions;

use App\Modules\Education\Question\Services\QuestionService;

class TransitionQuestionAction
{
    public function handle(...$params)
    {
        return app(QuestionService::class)->transition(...$params);
    }
}
