<?php

namespace App\Modules\HR\Payroll\Actions;

use App\Modules\HR\Payroll\Services\PayrollService;

class ListPayrollAction
{
    public function handle(...$params)
    {
        return app(PayrollService::class)->paginate(...$params);
    }
}
