<?php

namespace App\Modules\Education\ClassSessionFeedback\Actions;

use App\Modules\Education\ClassSessionFeedback\Services\ClassSessionFeedbackService;

class ListClassSessionFeedbackAction
{
    public function handle(...$params)
    {
        return app(ClassSessionFeedbackService::class)->paginate(...$params);
    }
}
