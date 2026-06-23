<?php

namespace App\Modules\Education\LeaveRequest\Actions;

use App\Modules\Education\LeaveRequest\Services\LeaveRequestService;

class CancelLeaveRequestAction
{
    public function handle(...$params)
    {
        return app(LeaveRequestService::class)->cancel(...$params);
    }
}
