<?php

namespace App\Modules\Education\ClassRoom\Actions;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\ClassRoom\Services\ClassService;

class UpdateClassAction
{
    public function handle($id, array $data): ClassRoom
    {
        return app(ClassService::class)->update($id, $data);
    }
}
