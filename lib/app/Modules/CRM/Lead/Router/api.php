<?php

use App\Modules\CRM\Lead\Http\Controllers\LeadController;
use App\Modules\CRM\Lead\Http\Controllers\LeadGuardianController;
use App\Modules\CRM\Lead\Http\Controllers\LeadStudentController;
use Illuminate\Support\Facades\Route;

// Lead CRUD + lifecycle (lead.md §2–§7).
Route::prefix('lead')->middleware('auth.tera')->group(function () {

    Route::get('/list', [LeadController::class, 'list'])->middleware('permission:lead.list');
    Route::get('/detail/{id}', [LeadController::class, 'detail'])->middleware('permission:lead.view');

    Route::post('/create', [LeadController::class, 'create'])->middleware('permission:lead.create');
    Route::put('/update/{id}', [LeadController::class, 'update'])->middleware('permission:lead.update');

    Route::post('/suspend/{id}', [LeadController::class, 'suspend'])->middleware('permission:lead.suspend');
    Route::post('/restore/{id}', [LeadController::class, 'restore'])->middleware('permission:lead.restore');

    Route::patch('/status/{id}', [LeadController::class, 'updateStatus'])->middleware('permission:lead.update');
    Route::post('/convert/{id}', [LeadController::class, 'convert'])->middleware('permission:lead.update');
    Route::post('/history/{id}', [LeadController::class, 'addHistory'])->middleware('permission:lead.update');

    // Guardians nested under a lead (lead.md §8).
    Route::get('/{leadId}/guardian/list', [LeadGuardianController::class, 'list'])->middleware('permission:lead.view');
    Route::post('/{leadId}/guardian/add', [LeadGuardianController::class, 'create'])->middleware('permission:lead.update');
    Route::put('/{leadId}/guardian/update/{id}', [LeadGuardianController::class, 'update'])->middleware('permission:lead.update');
    Route::delete('/{leadId}/guardian/delete/{id}', [LeadGuardianController::class, 'delete'])->middleware('permission:lead.update');

    // Student links nested under a lead (lead.md §9).
    Route::get('/{leadId}/student/list', [LeadStudentController::class, 'list'])->middleware('permission:lead.view');
    Route::post('/{leadId}/student/add', [LeadStudentController::class, 'create'])->middleware('permission:lead.update');
    Route::put('/{leadId}/student/update/{id}', [LeadStudentController::class, 'update'])->middleware('permission:lead.update');
    Route::delete('/{leadId}/student/delete/{id}', [LeadStudentController::class, 'delete'])->middleware('permission:lead.update');

});
