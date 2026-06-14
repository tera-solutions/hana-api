<?php

namespace App\Modules\Education\ClassSession\Actions;

use App\Modules\Education\ClassSession\Services\ClassSessionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListSessionAction
{
    public function handle(array $params): LengthAwarePaginator
    {
        return app(ClassSessionService::class)->paginate($params);
    }
}
