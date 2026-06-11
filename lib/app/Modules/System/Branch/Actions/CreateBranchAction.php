<?php

namespace App\Modules\System\Branch\Actions;

use App\Modules\System\Branch\Services\BranchService;

class CreateBranchAction
{
    public function handle(...$params)
    {
        return app(BranchService::class)->create(...$params);
    }
}
