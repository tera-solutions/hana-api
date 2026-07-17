<?php

namespace App\Modules\HR\Payroll\Actions;

use App\Modules\HR\Payroll\Services\PayrollService;

class GeneratePayrollAction
{
    public function handle(...$params)
    {
        return app(PayrollService::class)->generate(...$params);
    }
}
