<?php

namespace App\Modules\Education\Attendance\Actions;

use App\Modules\Education\Attendance\Services\AttendanceService;

class CreateAttendanceAction
{
    public function handle(...$params)
    {
        return app(AttendanceService::class)->create(...$params);
    }
}
