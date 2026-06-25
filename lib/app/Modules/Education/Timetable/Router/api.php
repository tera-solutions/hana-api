<?php

use App\Modules\Education\Timetable\Http\Controllers\TimetableController;
use Illuminate\Support\Facades\Route;

// Timetable management + calendar/schedule views (timetable-management.md §XII).
Route::prefix('timetable')->middleware('auth.tera')->group(function () {

    Route::get('/list', [TimetableController::class, 'list'])->middleware('permission:timetable.list');
    Route::get('/calendar', [TimetableController::class, 'calendar'])->middleware('permission:timetable.view');
    Route::get('/detail/{id}', [TimetableController::class, 'detail'])->middleware('permission:timetable.view');

    Route::post('/create', [TimetableController::class, 'create'])->middleware('permission:timetable.create');
    Route::put('/update/{id}', [TimetableController::class, 'update'])->middleware('permission:timetable.update');
    Route::delete('/delete/{id}', [TimetableController::class, 'delete'])->middleware('permission:timetable.delete');

    // Per-object schedules.
    Route::get('/teacher/{id}/schedule', [TimetableController::class, 'teacherSchedule'])->middleware('permission:timetable.view');
    Route::get('/student/{id}/schedule', [TimetableController::class, 'studentSchedule'])->middleware('permission:timetable.view');
    Route::get('/room/{id}/schedule', [TimetableController::class, 'roomSchedule'])->middleware('permission:timetable.view');

});
