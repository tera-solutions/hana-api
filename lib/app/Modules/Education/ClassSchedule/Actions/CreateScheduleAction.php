<?php

namespace App\Modules\Education\ClassSchedule\Actions;

use App\Modules\Education\ClassSchedule\Models\ClassSchedule;
use App\Modules\Education\ClassSchedule\Services\ClassScheduleService;

class CreateScheduleAction
{
    public function handle($classId, array $data): ClassSchedule
    {
        return app(ClassScheduleService::class)->create($classId, $data);
    }
}
