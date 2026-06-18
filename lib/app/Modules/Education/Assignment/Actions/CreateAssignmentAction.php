<?php

namespace App\Modules\Education\Assignment\Actions;

use App\Modules\Education\Assignment\Services\AssignmentService;

class CreateAssignmentAction
{
    public function handle(...$params)
    {
        return app(AssignmentService::class)->create(...$params);
    }
}
