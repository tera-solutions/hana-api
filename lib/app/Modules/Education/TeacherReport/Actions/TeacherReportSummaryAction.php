<?php

namespace App\Modules\Education\TeacherReport\Actions;

use App\Modules\Education\TeacherReport\Services\TeacherReportService;

class TeacherReportSummaryAction
{
    public function handle(...$params)
    {
        return app(TeacherReportService::class)->summary(...$params);
    }
}
