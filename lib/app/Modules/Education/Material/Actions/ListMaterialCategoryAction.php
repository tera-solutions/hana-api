<?php

namespace App\Modules\Education\Material\Actions;

use App\Modules\Education\Material\Services\MaterialCategoryService;

class ListMaterialCategoryAction
{
    public function handle(...$params)
    {
        return app(MaterialCategoryService::class)->paginate(...$params);
    }
}
