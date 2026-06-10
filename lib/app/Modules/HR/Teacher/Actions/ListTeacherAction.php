<?php

namespace App\Modules\HR\Teacher\Actions;

use App\Modules\HR\Teacher\Services\TeacherService;

class ListTeacherAction
{
    public function handle(...$params)
    {
        return app(TeacherService::class)->paginate(...$params);
    }
}
