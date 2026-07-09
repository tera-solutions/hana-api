<?php

namespace App\Modules\Education\Attendance\Actions;

use App\Modules\Education\Attendance\Services\AttendanceService;

class ExportAttendanceAction
{
    public function handle(...$params)
    {
        return app(AttendanceService::class)->export(...$params);
    }
}
