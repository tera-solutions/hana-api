<?php

namespace App\Modules\Education\ClassRoom\Actions;

use App\Modules\Education\ClassRoom\Models\ClassSchedule;
use App\Modules\Education\ClassRoom\Services\ClassScheduleService;

class CreateScheduleAction
{
    public function handle($classId, array $data): ClassSchedule
    {
        return app(ClassScheduleService::class)->create($classId, $data);
    }
}
