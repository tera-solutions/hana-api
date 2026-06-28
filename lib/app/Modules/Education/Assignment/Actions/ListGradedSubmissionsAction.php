<?php

namespace App\Modules\Education\Assignment\Actions;

use App\Modules\Education\Assignment\Services\AssignmentService;

class ListGradedSubmissionsAction
{
    public function handle(...$params)
    {
        return app(AssignmentService::class)->gradedSubmissions(...$params);
    }
}
