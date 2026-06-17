<?php

namespace App\Console\Commands;

use App\Modules\Education\Lesson\Services\LessonService;
use Illuminate\Console\Command;

/**
 * Advances lesson lifecycle on a schedule (lesson.md §6, §11): completes lessons
 * whose end time has passed, then locks completed lessons past the lock window.
 */
class AutoProgressLessons extends Command
{
    protected $signature = 'lessons:auto-progress';

    protected $description = 'Auto-complete finished lessons and auto-lock completed ones past the lock window.';

    public function handle(LessonService $lessons): int
    {
        $completed = $lessons->autoComplete();
        $locked = $lessons->autoLock();

        $this->info("Auto-completed {$completed} lesson(s); auto-locked {$locked} lesson(s).");

        return self::SUCCESS;
    }
}
