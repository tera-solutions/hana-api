<?php

namespace App\Modules\Education\Assignment\Actions;

use App\Modules\Education\Assignment\Services\AssignmentService;

class SummaryAssignmentAction
{
    public function handle(array $params = []): array
    {
        return app(AssignmentService::class)->summary($params);
    }
}
