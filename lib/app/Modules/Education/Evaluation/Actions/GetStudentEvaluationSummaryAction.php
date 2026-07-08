<?php

namespace App\Modules\Education\Evaluation\Actions;

use App\Modules\Education\Evaluation\Services\EvaluationService;

class GetStudentEvaluationSummaryAction
{
    public function handle(...$params)
    {
        return app(EvaluationService::class)->studentSummary(...$params);
    }
}
