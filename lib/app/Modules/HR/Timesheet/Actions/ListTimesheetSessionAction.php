<?php

namespace App\Modules\HR\Timesheet\Actions;

use App\Modules\HR\Timesheet\Services\TimesheetService;

class ListTimesheetSessionAction
{
    public function handle(...$params)
    {
        return app(TimesheetService::class)->sessions(...$params);
    }
}
