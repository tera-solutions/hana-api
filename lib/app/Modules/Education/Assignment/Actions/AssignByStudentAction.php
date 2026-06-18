<?php

namespace App\Modules\Education\Assignment\Actions;

use App\Modules\Education\Assignment\Services\AssignmentService;

class AssignByStudentAction
{
    public function handle($id, array $studentIds): array
    {
        return app(AssignmentService::class)->assignByStudent($id, $studentIds);
    }
}
