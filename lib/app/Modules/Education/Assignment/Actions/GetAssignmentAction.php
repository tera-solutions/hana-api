<?php

namespace App\Modules\Education\Assignment\Actions;

use App\Modules\Education\Assignment\Services\AssignmentService;

class GetAssignmentAction
{
    public function handle(...$params)
    {
        return app(AssignmentService::class)->detail(...$params);
    }
}
