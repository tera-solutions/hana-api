<?php

namespace App\Modules\System\Package\Actions;

use App\Modules\System\Package\Services\PackageService;

class ListPackageAction
{
    public function handle(...$params)
    {
        return app(PackageService::class)->paginate(...$params);
    }
}
