<?php

namespace App\Modules\Education\ClassRoom\Actions;

use App\Modules\Education\ClassRoom\Models\ClassSchedule;
use App\Modules\Education\ClassRoom\Services\ClassScheduleService;

class UpdateScheduleAction
{
    public function handle($id, array $data): ClassSchedule
    {
        return app(ClassScheduleService::class)->update($id, $data);
    }
}
