<?php

namespace App\Modules\Education\Student\Actions;

use App\Modules\Education\Student\Services\StudentService;

class DeleteStudentAction
{
    public function handle(...$params)
    {
        return app(StudentService::class)->delete(...$params);
    }
}
