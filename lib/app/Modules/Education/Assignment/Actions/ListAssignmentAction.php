<?php

namespace App\Modules\Education\Assignment\Actions;

use App\Modules\Education\Assignment\Services\AssignmentService;

class ListAssignmentAction
{
    public function handle(...$params)
    {
        return app(AssignmentService::class)->paginate(...$params);
    }
}
