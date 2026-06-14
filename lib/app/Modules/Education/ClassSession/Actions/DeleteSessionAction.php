<?php

namespace App\Modules\Education\ClassSession\Actions;

use App\Modules\Education\ClassSession\Services\ClassSessionService;

class DeleteSessionAction
{
    public function handle($id): void
    {
        app(ClassSessionService::class)->delete($id);
    }
}
