<?php

namespace App\Modules\Education\ClassSchedule\Actions;

use App\Modules\Education\ClassSchedule\Services\ClassScheduleService;

class DeleteScheduleAction
{
    public function handle($id): void
    {
        app(ClassScheduleService::class)->delete($id);
    }
}
