<?php

namespace App\Modules\CRM\ParentStudent\Actions;

use App\Modules\CRM\ParentStudent\Services\ParentStudentService;

class CreateParentStudentAction
{
    public function handle(...$params)
    {
        return app(ParentStudentService::class)->create(...$params);
    }
}
