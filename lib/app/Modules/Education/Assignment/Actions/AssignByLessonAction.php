<?php

namespace App\Modules\Education\Assignment\Actions;

use App\Modules\Education\Assignment\Services\AssignmentService;

class AssignByLessonAction
{
    public function handle($id, int $lessonId): array
    {
        return app(AssignmentService::class)->assignByLesson($id, $lessonId);
    }
}
