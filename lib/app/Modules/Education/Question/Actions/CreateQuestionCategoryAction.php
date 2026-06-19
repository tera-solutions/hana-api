<?php

namespace App\Modules\Education\Question\Actions;

use App\Modules\Education\Question\Services\QuestionCategoryService;

class CreateQuestionCategoryAction
{
    public function handle(...$params)
    {
        return app(QuestionCategoryService::class)->create(...$params);
    }
}
