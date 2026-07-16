<?php

namespace App\Modules\System\Setting\Actions;

use App\Modules\System\Setting\Services\SettingService;

class UpdateSettingAction
{
    public function handle(...$params)
    {
        return app(SettingService::class)->update(...$params);
    }
}
