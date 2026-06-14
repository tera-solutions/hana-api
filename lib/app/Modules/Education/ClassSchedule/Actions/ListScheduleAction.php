<?php

namespace App\Modules\Education\ClassSchedule\Actions;

use App\Modules\Education\ClassSchedule\Services\ClassScheduleService;
use Illuminate\Database\Eloquent\Collection;

class ListScheduleAction
{
    public function handle($classId): Collection
    {
        return app(ClassScheduleService::class)->list($classId);
    }
}
