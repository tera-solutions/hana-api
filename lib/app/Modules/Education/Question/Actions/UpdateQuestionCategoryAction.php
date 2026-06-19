<?php

namespace App\Modules\Education\Question\Actions;

use App\Modules\Education\Question\Services\QuestionCategoryService;

class UpdateQuestionCategoryAction
{
    public function handle(...$params)
    {
        return app(QuestionCategoryService::class)->update(...$params);
    }
}
