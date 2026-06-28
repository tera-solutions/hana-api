<?php

namespace App\Modules\Education\Student\Actions;

use App\Modules\Education\Student\Services\StudentService;

class SummaryStudentAction
{
    public function handle(array $params = []): array
    {
        return app(StudentService::class)->summary($params);
    }
}
