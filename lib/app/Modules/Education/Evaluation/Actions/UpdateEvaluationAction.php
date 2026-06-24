<?php

namespace App\Modules\Education\Evaluation\Actions;

use App\Modules\Education\Evaluation\Services\EvaluationService;

class UpdateEvaluationAction
{
    public function handle(...$params)
    {
        return app(EvaluationService::class)->update(...$params);
    }
}
