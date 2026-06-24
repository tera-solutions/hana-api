<?php

namespace App\Modules\Education\Evaluation\Actions;

use App\Modules\Education\Evaluation\Services\EvaluationService;

/**
 * Drives a status transition (submit / approve / reject / lock) by delegating to the
 * matching service method.
 */
class TransitionEvaluationAction
{
    public function handle(string $transition, $id)
    {
        return app(EvaluationService::class)->{$transition}($id);
    }
}
