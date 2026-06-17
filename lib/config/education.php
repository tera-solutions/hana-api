<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lesson auto-lock window
    |--------------------------------------------------------------------------
    |
    | Days after a lesson is completed before it auto-locks (lesson.md §11).
    | Used by the lessons:auto-progress scheduled command.
    |
    */
    'lesson_auto_lock_days' => (int) env('LESSON_AUTO_LOCK_DAYS', 7),
];
