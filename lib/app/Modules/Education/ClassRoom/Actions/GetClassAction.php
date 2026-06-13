<?php

namespace App\Modules\Education\ClassRoom\Actions;

use App\Modules\Education\ClassRoom\Services\ClassService;

class GetClassAction
{
    public function handle($id): array
    {
        return app(ClassService::class)->detail($id);
    }
}
