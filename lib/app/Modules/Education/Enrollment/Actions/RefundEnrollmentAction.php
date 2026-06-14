<?php

namespace App\Modules\Education\Enrollment\Actions;

use App\Modules\Education\Enrollment\Models\Enrollment;
use App\Modules\Education\Enrollment\Services\EnrollmentService;

class RefundEnrollmentAction
{
    public function handle($id): Enrollment
    {
        return app(EnrollmentService::class)->refund($id);
    }
}
