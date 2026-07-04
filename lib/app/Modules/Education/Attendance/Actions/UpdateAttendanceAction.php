<?php

namespace App\Modules\Education\Attendance\Actions;

use App\Modules\Education\Attendance\Services\AttendanceService;

class UpdateAttendanceAction
{
    public function handle(...$params)
    {
        return app(AttendanceService::class)->update(...$params);
    }
}
