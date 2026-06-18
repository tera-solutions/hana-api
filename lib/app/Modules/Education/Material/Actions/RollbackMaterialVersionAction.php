<?php

namespace App\Modules\Education\Material\Actions;

use App\Modules\Education\Material\Services\MaterialService;

class RollbackMaterialVersionAction
{
    public function handle(...$params)
    {
        return app(MaterialService::class)->rollbackVersion(...$params);
    }
}
