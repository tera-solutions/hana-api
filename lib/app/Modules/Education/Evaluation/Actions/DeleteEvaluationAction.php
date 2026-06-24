<?php

namespace App\Modules\Education\Evaluation\Actions;

use App\Modules\Education\Evaluation\Services\EvaluationService;

class DeleteEvaluationAction
{
    public function handle(...$params)
    {
        return app(EvaluationService::class)->delete(...$params);
    }
}
