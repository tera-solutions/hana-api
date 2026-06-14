<?php

namespace App\Modules\Education\Enrollment\Actions;

use App\Modules\Education\Enrollment\Services\EnrollmentService;

class GetEnrollmentAction
{
    public function handle($id): array
    {
        return app(EnrollmentService::class)->detail($id);
    }
}
