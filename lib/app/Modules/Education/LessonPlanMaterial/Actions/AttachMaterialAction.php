<?php

namespace App\Modules\Education\LessonPlanMaterial\Actions;

use App\Modules\Education\LessonPlanMaterial\Services\LessonPlanMaterialService;

class AttachMaterialAction
{
    public function handle(...$params)
    {
        return app(LessonPlanMaterialService::class)->attachMaterial(...$params);
    }
}
