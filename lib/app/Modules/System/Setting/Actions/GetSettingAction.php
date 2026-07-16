<?php

namespace App\Modules\System\Setting\Actions;

use App\Modules\System\Setting\Services\SettingService;

class GetSettingAction
{
    public function handle(...$params)
    {
        return app(SettingService::class)->find(...$params);
    }
}
