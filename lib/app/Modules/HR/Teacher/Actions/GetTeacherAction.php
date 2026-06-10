<?php

namespace App\Modules\HR\Teacher\Actions;

use App\Modules\HR\Teacher\Services\TeacherService;

class GetTeacherAction
{
    public function handle(...$params)
    {
        return app(TeacherService::class)->find(...$params);
    }
}
