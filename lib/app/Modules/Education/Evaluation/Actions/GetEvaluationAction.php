<?php

namespace App\Modules\Education\Evaluation\Actions;

use App\Modules\Education\Evaluation\Services\EvaluationService;

class GetEvaluationAction
{
    public function handle(...$params)
    {
        return app(EvaluationService::class)->find(...$params);
    }
}
