<?php

namespace App\Modules\Education\ClassSession\Actions;

use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\ClassSession\Services\ClassSessionService;

class GetSessionAction
{
    public function handle($id): ClassSession
    {
        return app(ClassSessionService::class)->detail($id);
    }
}
