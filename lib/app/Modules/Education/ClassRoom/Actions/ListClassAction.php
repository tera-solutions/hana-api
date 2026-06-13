<?php

namespace App\Modules\Education\ClassRoom\Actions;

use App\Modules\Education\ClassRoom\Services\ClassService;

class ListClassAction
{
    public function handle(array $params = [])
    {
        return app(ClassService::class)->paginate($params);
    }
}
