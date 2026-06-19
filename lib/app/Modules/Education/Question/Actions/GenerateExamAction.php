<?php

namespace App\Modules\Education\Question\Actions;

use App\Modules\Education\Question\Services\GenerateExamService;

class GenerateExamAction
{
    public function handle(...$params)
    {
        return app(GenerateExamService::class)->generate(...$params);
    }
}
