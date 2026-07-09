<?php

namespace App\Modules\HR\Achievement\Actions;

use App\Modules\HR\Achievement\Services\AchievementService;

class GetAchievementSummaryAction
{
    public function handle(...$params)
    {
        return app(AchievementService::class)->summary(...$params);
    }
}
