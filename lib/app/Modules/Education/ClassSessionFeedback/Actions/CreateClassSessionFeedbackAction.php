<?php

namespace App\Modules\Education\ClassSessionFeedback\Actions;

use App\Modules\Education\ClassSessionFeedback\Services\ClassSessionFeedbackService;

class CreateClassSessionFeedbackAction
{
    public function handle(...$params)
    {
        return app(ClassSessionFeedbackService::class)->create(...$params);
    }
}
