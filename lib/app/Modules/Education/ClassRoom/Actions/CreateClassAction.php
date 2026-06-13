<?php

namespace App\Modules\Education\ClassRoom\Actions;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\ClassRoom\Services\ClassService;

class CreateClassAction
{
    public function handle(array $data): ClassRoom
    {
        return app(ClassService::class)->create($data);
    }
}
