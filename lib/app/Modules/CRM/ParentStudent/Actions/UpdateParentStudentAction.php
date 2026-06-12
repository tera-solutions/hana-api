<?php

namespace App\Modules\CRM\ParentStudent\Actions;

use App\Modules\CRM\ParentStudent\Services\ParentStudentService;

class UpdateParentStudentAction
{
    public function handle(...$params)
    {
        return app(ParentStudentService::class)->update(...$params);
    }
}
