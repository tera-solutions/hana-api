<?php

namespace App\Modules\Education\ClassSession\Actions;

use App\Modules\Education\ClassSession\Services\ClassSessionService;

class GenerateSessionAction
{
    /**
     * @return array{created: int, skipped: int}
     */
    public function handle($classId, array $data): array
    {
        return app(ClassSessionService::class)->generate($classId, $data);
    }
}
