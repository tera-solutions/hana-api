<?php

namespace App\Modules\Education\Assignment\Actions;

use App\Modules\Education\Assignment\Services\AssignmentService;

class SubmitAssignmentAction
{
    public function handle(...$params)
    {
        return app(AssignmentService::class)->submit(...$params);
    }
}
