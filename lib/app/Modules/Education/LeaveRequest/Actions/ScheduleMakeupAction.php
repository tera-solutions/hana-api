<?php

namespace App\Modules\Education\LeaveRequest\Actions;

use App\Modules\Education\LeaveRequest\Services\LeaveRequestService;

class ScheduleMakeupAction
{
    public function handle(...$params)
    {
        return app(LeaveRequestService::class)->scheduleMakeup(...$params);
    }
}
