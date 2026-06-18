<?php

namespace App\Modules\Education\Assignment\Actions;

use App\Modules\Education\Assignment\Services\AssignmentService;

class AssignByGroupAction
{
    public function handle($id, int $levelId): array
    {
        return app(AssignmentService::class)->assignByGroup($id, $levelId);
    }
}
