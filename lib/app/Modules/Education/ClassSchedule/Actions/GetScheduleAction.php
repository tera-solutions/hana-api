<?php

namespace App\Modules\Education\ClassSchedule\Actions;

use App\Modules\Education\ClassSchedule\Models\ClassSchedule;
use App\Modules\Education\ClassSchedule\Services\ClassScheduleService;

class GetScheduleAction
{
    public function handle($id): ClassSchedule
    {
        return app(ClassScheduleService::class)->find($id);
    }
}
