<?php

namespace App\Modules\Education\EvaluationCriteriaTemplate\Actions;

use App\Modules\Education\EvaluationCriteriaTemplate\Services\EvaluationCriteriaTemplateService;

class ListEvaluationCriteriaTemplateAction
{
    public function __construct(private EvaluationCriteriaTemplateService $service) {}

    public function handle(array $params)
    {
        return $this->service->paginate($params);
    }
}
