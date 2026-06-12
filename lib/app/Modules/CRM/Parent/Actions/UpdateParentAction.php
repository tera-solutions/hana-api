<?php

namespace App\Modules\CRM\Parent\Actions;

use App\Modules\CRM\Parent\Services\ParentService;

class UpdateParentAction
{
    public function handle(...$params)
    {
        return app(ParentService::class)->update(...$params);
    }
}
