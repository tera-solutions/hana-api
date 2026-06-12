<?php

namespace App\Modules\CRM\ParentStudent\Actions;

use App\Modules\CRM\ParentStudent\Services\ParentStudentService;

class DeleteParentStudentAction
{
    public function handle(...$params)
    {
        return app(ParentStudentService::class)->delete(...$params);
    }
}
