<?php

namespace App\Modules\CRM\ParentStudent\Actions;

use App\Modules\CRM\ParentStudent\Services\ParentStudentService;

class GetParentStudentAction
{
    public function handle(...$params)
    {
        return app(ParentStudentService::class)->find(...$params);
    }
}
