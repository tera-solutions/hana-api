<?php

use App\Modules\Education\TeacherReport\Http\Controllers\TeacherReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('teacher-report')->middleware(['auth.tera', 'subscription.feature:advanced_reports'])->group(function () {

    Route::get('/summary', [TeacherReportController::class, 'summary'])->middleware('permission:teacher_report.view');

});
