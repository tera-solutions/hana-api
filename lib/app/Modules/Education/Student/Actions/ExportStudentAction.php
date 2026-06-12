<?php

namespace App\Modules\Education\Student\Actions;

use App\Modules\Education\Student\Services\StudentService;

class ExportStudentAction
{
    public function handle(...$params)
    {
        return app(StudentService::class)->export(...$params);
    }
}
