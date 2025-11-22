<?php

use App\Http\Controllers\Employee\AcademicClassController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Academic Classes management routes
|--------------------------------------------------------------------------
|
| Routes for managing academic classes (class assignments in specific academic year with homeroom teacher)
|
*/

Route::prefix('academic-classes')->name('employee.academic_classes.')->middleware('auth:employee')->group(function () {
    Route::get('/', [AcademicClassController::class, 'index'])->name('index');
    Route::get('/data', [AcademicClassController::class, 'getData'])->name('data');
    Route::get('/create', [AcademicClassController::class, 'create'])->name('create');
    Route::post('/', [AcademicClassController::class, 'store'])->name('store');
    Route::get('/edit/{id}', [AcademicClassController::class, 'edit'])->name('edit');
    Route::put('/{id}', [AcademicClassController::class, 'update'])->name('update');
    Route::get('/get-new-id', [AcademicClassController::class, 'getNewAcademicClassId'])->name('getNewId');
    Route::delete('/{id}', [AcademicClassController::class, 'destroy'])->name('destroy');
    // API endpoints for AJAX lists used by student-classes form
    Route::get('/api/years', [AcademicClassController::class, 'apiYears'])->name('api.years');
    Route::get('/api/by-year/{academic_year_id}', [AcademicClassController::class, 'apiClassesByYear'])->name('api.by_year');
    Route::get('/api/assigned-students/{academic_year_id}', [AcademicClassController::class, 'apiAssignedStudentsByYear'])->name('api.assigned_students');
});
