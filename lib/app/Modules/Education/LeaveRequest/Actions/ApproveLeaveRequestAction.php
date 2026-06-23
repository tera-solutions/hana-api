<?php

namespace App\Modules\Education\LeaveRequest\Actions;

use App\Modules\Education\LeaveRequest\Services\LeaveRequestService;

class ApproveLeaveRequestAction
{
    public function handle(...$params)
    {
        return app(LeaveRequestService::class)->approve(...$params);
    }
}
