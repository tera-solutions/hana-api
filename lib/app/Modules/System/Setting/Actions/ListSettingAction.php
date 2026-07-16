<?php

namespace App\Modules\System\Setting\Actions;

use App\Modules\System\Setting\Services\SettingService;

class ListSettingAction
{
    public function handle(...$params)
    {
        return app(SettingService::class)->paginate(...$params);
    }
}
