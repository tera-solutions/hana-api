<?php

use App\Modules\CRM\Lead\Http\Controllers\LeadController;
use App\Modules\CRM\Lead\Http\Controllers\LeadGuardianController;
use App\Modules\CRM\Lead\Http\Controllers\LeadStudentController;
use Illuminate\Support\Facades\Route;

// Lead CRUD + lifecycle (lead.md §2–§7).
Route::prefix('lead')->middleware('auth.tera')->group(function () {

    Route::get('/list', [LeadController::class, 'list'])->middleware('permission:crm_lead.list');
    Route::get('/detail/{id}', [LeadController::class, 'detail'])->middleware('permission:crm_lead.view');

    Route::post('/create', [LeadController::class, 'create'])->middleware('permission:crm_lead.create');
    Route::put('/update/{id}', [LeadController::class, 'update'])->middleware('permission:crm_lead.update');

    Route::post('/suspend/{id}', [LeadController::class, 'suspend'])->middleware('permission:crm_lead.suspend');
    Route::post('/restore/{id}', [LeadController::class, 'restore'])->middleware('permission:crm_lead.restore');

    // Guardians nested under a lead (lead.md §8).
    Route::get('/{leadId}/guardian/list', [LeadGuardianController::class, 'list'])->middleware('permission:crm_lead.view');
    Route::post('/{leadId}/guardian/add', [LeadGuardianController::class, 'create'])->middleware('permission:crm_lead.update');
    Route::put('/{leadId}/guardian/update/{id}', [LeadGuardianController::class, 'update'])->middleware('permission:crm_lead.update');
    Route::delete('/{leadId}/guardian/delete/{id}', [LeadGuardianController::class, 'delete'])->middleware('permission:crm_lead.update');

    // Student links nested under a lead (lead.md §9).
    Route::get('/{leadId}/student/list', [LeadStudentController::class, 'list'])->middleware('permission:crm_lead.view');
    Route::post('/{leadId}/student/add', [LeadStudentController::class, 'create'])->middleware('permission:crm_lead.update');
    Route::put('/{leadId}/student/update/{id}', [LeadStudentController::class, 'update'])->middleware('permission:crm_lead.update');
    Route::delete('/{leadId}/student/delete/{id}', [LeadStudentController::class, 'delete'])->middleware('permission:crm_lead.update');

});
