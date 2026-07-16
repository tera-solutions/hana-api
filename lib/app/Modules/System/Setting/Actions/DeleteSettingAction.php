<?php

namespace App\Modules\System\Setting\Actions;

use App\Modules\System\Setting\Services\SettingService;

class DeleteSettingAction
{
    public function handle(...$params)
    {
        return app(SettingService::class)->delete(...$params);
    }
}
