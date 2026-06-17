<?php

namespace App\Modules\Education\LessonPlanMaterial\Actions;

use App\Modules\Education\LessonPlanMaterial\Services\LessonPlanMaterialService;

class DetachMaterialAction
{
    public function handle(...$params)
    {
        return app(LessonPlanMaterialService::class)->detachMaterial(...$params);
    }
}
