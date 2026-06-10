<?php

namespace App\Modules\Education\Teacher\Actions;

use App\Modules\Education\Teacher\Services\TeacherService;

class UpdateTeacherAction
{
    public function handle(...$params)
    {
        return app(TeacherService::class)->update(...$params);
    }
}
