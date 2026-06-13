<?php

namespace App\Modules\Education\ClassRoom\Actions;

use App\Modules\Education\ClassRoom\Services\ClassScheduleService;

class DeleteScheduleAction
{
    public function handle($id): void
    {
        app(ClassScheduleService::class)->delete($id);
    }
}
