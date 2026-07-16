<?php

namespace App\Modules\System\Setting\Actions;

use App\Modules\System\Setting\Services\SettingService;

class CreateSettingAction
{
    public function handle(...$params)
    {
        return app(SettingService::class)->create(...$params);
    }
}
