<?php

namespace App\Modules\Education\ClassSession\Actions;

use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\ClassSession\Services\ClassSessionService;

class CreateSessionAction
{
    public function handle(array $data): ClassSession
    {
        return app(ClassSessionService::class)->create($data);
    }
}
