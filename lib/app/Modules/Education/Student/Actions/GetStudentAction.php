<?php

namespace App\Modules\Education\Student\Actions;

use App\Modules\Education\Student\Services\StudentService;

class GetStudentAction
{
    public function handle(...$params)
    {
        return app(StudentService::class)->detail(...$params);
    }
}
