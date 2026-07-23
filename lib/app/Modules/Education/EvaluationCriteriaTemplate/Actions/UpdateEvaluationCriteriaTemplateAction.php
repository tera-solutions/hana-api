<?php

namespace App\Modules\Education\EvaluationCriteriaTemplate\Actions;

use App\Modules\Education\EvaluationCriteriaTemplate\Services\EvaluationCriteriaTemplateService;

class UpdateEvaluationCriteriaTemplateAction
{
    public function __construct(private EvaluationCriteriaTemplateService $service) {}

    public function handle($id, array $data)
    {
        return $this->service->update($id, $data);
    }
}
