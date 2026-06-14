<?php

namespace App\Modules\Education\ClassSchedule\Actions;

use App\Modules\Education\ClassSchedule\Models\ClassSchedule;
use App\Modules\Education\ClassSchedule\Services\ClassScheduleService;

class UpdateScheduleAction
{
    public function handle($id, array $data): ClassSchedule
    {
        return app(ClassScheduleService::class)->update($id, $data);
    }
}
