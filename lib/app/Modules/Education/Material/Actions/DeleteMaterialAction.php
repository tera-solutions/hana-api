<?php

namespace App\Modules\Education\Material\Actions;

use App\Modules\Education\Material\Services\MaterialService;

class DeleteMaterialAction
{
    public function handle(...$params)
    {
        return app(MaterialService::class)->delete(...$params);
    }
}
