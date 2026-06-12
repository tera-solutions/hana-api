<?php

namespace App\Modules\CRM\Parent\Actions;

use App\Modules\CRM\Parent\Services\ParentService;

class SuspendParentAction
{
    public function handle(...$params)
    {
        return app(ParentService::class)->suspend(...$params);
    }
}
