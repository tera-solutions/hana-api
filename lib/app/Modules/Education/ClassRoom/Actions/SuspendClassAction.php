<?php

namespace App\Modules\Education\ClassRoom\Actions;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\ClassRoom\Services\ClassService;

class SuspendClassAction
{
    public function handle($id, array $data): ClassRoom
    {
        return app(ClassService::class)->suspend($id, $data);
    }
}
