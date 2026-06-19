<?php

namespace App\Modules\Education\Question\Actions;

use App\Modules\Education\Question\Services\QuestionCategoryService;

class ListQuestionCategoryAction
{
    public function handle(...$params)
    {
        return app(QuestionCategoryService::class)->paginate(...$params);
    }
}
