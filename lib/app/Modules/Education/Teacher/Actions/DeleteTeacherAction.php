<?php

namespace App\Modules\Education\Teacher\Actions;

use App\Modules\Education\Teacher\Services\TeacherService;

class DeleteTeacherAction
{
    public function handle(...$params)
    {
        return app(TeacherService::class)->delete(...$params);
    }
}
