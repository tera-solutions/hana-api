<?php

namespace App\Modules\CRM\Parent\Actions;

use App\Modules\CRM\Parent\Services\ParentService;

class CreateParentAction
{
    public function handle(...$params)
    {
        return app(ParentService::class)->create(...$params);
    }
}
