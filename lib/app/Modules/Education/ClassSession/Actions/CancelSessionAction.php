<?php

namespace App\Modules\Education\ClassSession\Actions;

use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\ClassSession\Services\ClassSessionService;

class CancelSessionAction
{
    public function handle($id, array $data): ClassSession
    {
        return app(ClassSessionService::class)->cancel($id, $data);
    }
}
