<?php

namespace App\Modules\Education\Material\Actions;

use App\Modules\Education\Material\Services\MaterialService;

class AttachMaterialAction
{
    public function handle(...$params)
    {
        return app(MaterialService::class)->attach(...$params);
    }
}
