<?php

namespace App\Modules\Education\Student\Actions;

use App\Modules\Education\Student\Services\StudentService;

class ListStudentAction
{
    public function handle(...$params)
    {
        return app(StudentService::class)->paginate(...$params);
    }
}
