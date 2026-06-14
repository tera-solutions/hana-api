<?php

namespace App\Modules\Education\Enrollment\Actions;

use App\Modules\Education\Enrollment\Services\EnrollmentService;

class ListEnrollmentAction
{
    public function handle(array $params = [])
    {
        return app(EnrollmentService::class)->paginate($params);
    }
}
