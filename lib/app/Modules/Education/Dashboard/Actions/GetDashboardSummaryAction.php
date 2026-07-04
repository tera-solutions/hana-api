<?php

namespace App\Modules\Education\Dashboard\Actions;

use App\Modules\Education\Dashboard\Services\DashboardService;

class GetDashboardSummaryAction
{
    public function handle(?string $date = null): array
    {
        return app(DashboardService::class)->summary($date);
    }
}
