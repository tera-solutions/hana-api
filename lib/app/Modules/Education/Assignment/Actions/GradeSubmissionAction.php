<?php

namespace App\Modules\Education\Assignment\Actions;

use App\Modules\Education\Assignment\Services\AssignmentService;

class GradeSubmissionAction
{
    public function handle(...$params)
    {
        return app(AssignmentService::class)->grade(...$params);
    }
}
