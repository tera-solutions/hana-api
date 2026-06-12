<?php

namespace App\Modules\CRM\ParentStudent\Actions;

use App\Modules\CRM\ParentStudent\Services\ParentStudentService;

class ListParentStudentAction
{
    public function handle(...$params)
    {
        return app(ParentStudentService::class)->paginate(...$params);
    }
}
