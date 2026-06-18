<?php

namespace App\Modules\Education\Assignment\Actions;

use App\Modules\Education\Assignment\Services\AssignmentService;

class PublishAssignmentAction
{
    public function handle(...$params)
    {
        return app(AssignmentService::class)->publish(...$params);
    }
}
