<?php

namespace App\Modules\Education\EvaluationCriteriaTemplate\Actions;

use App\Modules\Education\EvaluationCriteriaTemplate\Services\EvaluationCriteriaTemplateService;

class RestoreEvaluationCriteriaTemplateAction
{
    public function __construct(private EvaluationCriteriaTemplateService $service) {}

    public function handle($id)
    {
        return $this->service->restore($id);
    }
}
