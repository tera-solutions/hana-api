<?php

namespace App\Modules\Education\Material\Actions;

use App\Modules\Education\Material\Services\MaterialService;

class ListMaterialMappingAction
{
    public function handle(...$params)
    {
        return app(MaterialService::class)->mappings(...$params);
    }
}
