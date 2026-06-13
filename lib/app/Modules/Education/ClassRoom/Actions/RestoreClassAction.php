<?php

namespace App\Modules\Education\ClassRoom\Actions;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\ClassRoom\Services\ClassService;

class RestoreClassAction
{
    public function handle($id): ClassRoom
    {
        return app(ClassService::class)->restore($id);
    }
}
