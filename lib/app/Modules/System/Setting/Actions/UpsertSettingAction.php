<?php

namespace App\Modules\System\Setting\Actions;

use App\Modules\System\Setting\Services\SettingService;

class UpsertSettingAction
{
    public function handle(...$params)
    {
        return app(SettingService::class)->upsert(...$params);
    }
}
