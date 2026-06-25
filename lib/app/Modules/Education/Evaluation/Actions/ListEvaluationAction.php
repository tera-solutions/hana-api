<?php

namespace App\Modules\Education\Evaluation\Actions;

use App\Modules\Education\Evaluation\Services\EvaluationService;

class ListEvaluationAction
{
    public function handle(...$params)
    {
        return app(EvaluationService::class)->paginate(...$params);
    }
}
