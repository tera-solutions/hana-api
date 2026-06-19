<?php

namespace App\Modules\Education\Question\Actions;

use App\Modules\Education\Question\Services\QuestionImportService;

class ImportQuestionAction
{
    public function handle(...$params)
    {
        return app(QuestionImportService::class)->import(...$params);
    }
}
