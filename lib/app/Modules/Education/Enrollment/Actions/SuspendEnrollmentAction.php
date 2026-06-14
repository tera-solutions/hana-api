<?php

namespace App\Modules\Education\Enrollment\Actions;

use App\Modules\Education\Enrollment\Models\Enrollment;
use App\Modules\Education\Enrollment\Services\EnrollmentService;

class SuspendEnrollmentAction
{
    public function handle($id, array $data): Enrollment
    {
        return app(EnrollmentService::class)->suspend($id, $data);
    }
}
