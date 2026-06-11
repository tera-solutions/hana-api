<?php

namespace App\Modules\System\Branch\Actions;

use App\Modules\System\Branch\Services\BranchService;

class GetBranchAction
{
    public function handle(...$params)
    {
        return app(BranchService::class)->detail(...$params);
    }
}
