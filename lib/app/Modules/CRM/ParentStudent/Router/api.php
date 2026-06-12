<?php

use App\Modules\CRM\ParentStudent\Http\Controllers\ParentStudentController;
use Illuminate\Support\Facades\Route;

Route::prefix('parent-student')->middleware('auth.tera')->group(function () {

    Route::get('/list', [ParentStudentController::class, 'list'])->middleware('permission:parent_student.list');
    Route::get('/detail/{id}', [ParentStudentController::class, 'detail'])->middleware('permission:parent_student.view');

    Route::post('/create', [ParentStudentController::class, 'create'])->middleware('permission:parent_student.create');
    Route::put('/update/{id}', [ParentStudentController::class, 'update'])->middleware('permission:parent_student.update');
    Route::delete('/delete/{id}', [ParentStudentController::class, 'delete'])->middleware('permission:parent_student.delete');

});
