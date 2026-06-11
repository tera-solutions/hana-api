<?php

namespace App\Modules\System\Branch\Actions;

use App\Modules\System\Branch\Services\BranchService;

class UpdateBranchAction
{
    public function handle(...$params)
    {
        return app(BranchService::class)->update(...$params);
    }
}
