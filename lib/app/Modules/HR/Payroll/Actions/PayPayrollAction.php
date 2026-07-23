<?php

namespace App\Modules\HR\Payroll\Actions;

use App\Modules\HR\Payroll\Services\PayrollService;

class PayPayrollAction
{
    public function __construct(private PayrollService $service) {}

    public function handle(int $id)
    {
        return $this->service->pay($id);
    }
}
