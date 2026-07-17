<?php

use App\Modules\HR\Timesheet\Http\Controllers\TimesheetController;
use Illuminate\Support\Facades\Route;

// A teacher's own worked sessions, derived from ClassSession + Attendance — see
// TimesheetController docblock. No admin/cross-teacher view here.
Route::prefix('timesheet')->middleware('auth.tera')->group(function () {

    Route::get('/list', [TimesheetController::class, 'list'])->middleware('permission:timesheet.view');
    Route::get('/summary', [TimesheetController::class, 'summary'])->middleware('permission:timesheet.view');

});
