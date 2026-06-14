<?php

namespace App\Modules\Education\Enrollment\Actions;

use App\Modules\Education\Enrollment\Models\Enrollment;
use App\Modules\Education\Enrollment\Services\EnrollmentService;

class CreateEnrollmentAction
{
    public function handle(array $data): Enrollment
    {
        return app(EnrollmentService::class)->create($data);
    }
}
