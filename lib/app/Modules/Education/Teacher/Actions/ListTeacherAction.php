<?php

namespace App\Modules\Education\Teacher\Actions;

use App\Modules\Education\Teacher\Services\TeacherService;

class ListTeacherAction
{
    public function handle(...$params)
    {
        return app(TeacherService::class)->paginate(...$params);
    }
}
