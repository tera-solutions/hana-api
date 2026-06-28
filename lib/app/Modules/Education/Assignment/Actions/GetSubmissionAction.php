<?php

namespace App\Modules\Education\Assignment\Actions;

use App\Modules\Education\Assignment\Services\AssignmentService;

class GetSubmissionAction
{
    public function handle(...$params)
    {
        return app(AssignmentService::class)->submissionDetail(...$params);
    }
}
