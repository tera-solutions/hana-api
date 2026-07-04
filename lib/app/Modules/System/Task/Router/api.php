<?php

use App\Modules\System\Task\Http\Controllers\TaskAttachmentController;
use App\Modules\System\Task\Http\Controllers\TaskChecklistController;
use App\Modules\System\Task\Http\Controllers\TaskCommentController;
use App\Modules\System\Task\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

// Internal task management (task-management.md §XI).
Route::prefix('task')->middleware('auth.tera')->group(function () {

    Route::get('/list', [TaskController::class, 'list'])->middleware('permission:task.list');
    Route::get('/detail/{id}', [TaskController::class, 'detail'])->middleware('permission:task.view');
    Route::post('/create', [TaskController::class, 'create'])->middleware('permission:task.create');
    Route::put('/update/{id}', [TaskController::class, 'update'])->middleware('permission:task.update');
    Route::delete('/delete/{id}', [TaskController::class, 'delete'])->middleware('permission:task.delete');

    // Comments — reuse the task codes (reads → view, writes → update).
    Route::get('/{id}/comment/list', [TaskCommentController::class, 'index'])->middleware('permission:task.view');
    Route::post('/{id}/comment/create', [TaskCommentController::class, 'create'])->middleware('permission:task.update');

    // Checklists — reuse the task codes (reads → view, writes → update).
    Route::get('/{id}/checklist/list', [TaskChecklistController::class, 'index'])->middleware('permission:task.view');
    Route::post('/{id}/checklist/create', [TaskChecklistController::class, 'create'])->middleware('permission:task.update');
    Route::put('/checklist/update/{id}', [TaskChecklistController::class, 'update'])->middleware('permission:task.update');
    Route::delete('/checklist/delete/{id}', [TaskChecklistController::class, 'delete'])->middleware('permission:task.update');

    // Attachments — reuse the task codes (reads → view, writes → update).
    Route::get('/{id}/attachment/list', [TaskAttachmentController::class, 'index'])->middleware('permission:task.view');
    Route::post('/{id}/attachment/create', [TaskAttachmentController::class, 'create'])->middleware('permission:task.update');
    Route::delete('/attachment/delete/{id}', [TaskAttachmentController::class, 'delete'])->middleware('permission:task.update');

});
