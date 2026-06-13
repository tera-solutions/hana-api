<?php

namespace App\Modules\Education\ClassRoom\Actions;

use App\Modules\Education\ClassRoom\Services\ClassScheduleService;
use Illuminate\Database\Eloquent\Collection;

class ListScheduleAction
{
    public function handle($classId): Collection
    {
        return app(ClassScheduleService::class)->list($classId);
    }
}
