<?php

namespace App\Modules\HR\Teacher\Actions;

use App\Modules\HR\Teacher\Services\TeacherService;

class UpdateTeacherAction
{
    public function handle(...$params)
    {
        return app(TeacherService::class)->update(...$params);
    }
}
