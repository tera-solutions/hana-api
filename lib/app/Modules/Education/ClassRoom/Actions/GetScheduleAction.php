<?php

namespace App\Modules\Education\ClassRoom\Actions;

use App\Modules\Education\ClassRoom\Models\ClassSchedule;
use App\Modules\Education\ClassRoom\Services\ClassScheduleService;

class GetScheduleAction
{
    public function handle($id): ClassSchedule
    {
        return app(ClassScheduleService::class)->find($id);
    }
}
