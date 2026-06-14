<?php

namespace App\Modules\Education\ClassSession\Actions;

use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\ClassSession\Services\ClassSessionService;

class UpdateSessionAction
{
    public function handle($id, array $data): ClassSession
    {
        return app(ClassSessionService::class)->update($id, $data);
    }
}
