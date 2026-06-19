<?php

namespace App\Modules\Education\Question\Actions;

use App\Modules\Education\Question\Services\QuestionCategoryService;

class DeleteQuestionCategoryAction
{
    public function handle(...$params)
    {
        return app(QuestionCategoryService::class)->delete(...$params);
    }
}
