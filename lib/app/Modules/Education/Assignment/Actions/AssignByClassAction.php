<?php

namespace App\Modules\Education\Assignment\Actions;

use App\Modules\Education\Assignment\Services\AssignmentService;

class AssignByClassAction
{
    public function handle($id, int $classRoomId): array
    {
        return app(AssignmentService::class)->assignByClass($id, $classRoomId);
    }
}
