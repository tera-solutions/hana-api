<?php

namespace App\Modules\Education\ClassRoom\Actions;

use App\Modules\Education\ClassRoom\Services\ClassService;

class SummaryClassAction
{
    public function handle(array $params = []): array
    {
        return app(ClassService::class)->summary($params);
    }
}
