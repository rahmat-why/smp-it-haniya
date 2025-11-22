<?php

use App\Http\Controllers\Employee\AcademicYearController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Academic Years management routes
|--------------------------------------------------------------------------
|
| Routes for managing academic years under the employee area.
|
*/

Route::prefix('academic-years')->name('employee.academic_years.')->middleware('auth:employee')->group(function () {
    Route::get('/', [AcademicYearController::class, 'index'])->name('index');
    Route::get('/data', [AcademicYearController::class, 'getData'])->name('data');
    Route::get('/create', [AcademicYearController::class, 'create'])->name('create');
    Route::post('/', [AcademicYearController::class, 'store'])->name('store');
    Route::get('/get/{id}', [AcademicYearController::class, 'getAcademicYear'])->name('get');
    Route::get('/edit/{id}', [AcademicYearController::class, 'edit'])->name('edit');
    Route::put('/{id}', [AcademicYearController::class, 'update'])->name('update');
    Route::get('/get-new-id', [AcademicYearController::class, 'getNewAcademicYearId'])->name('getNewId');
    Route::delete('/{id}', [AcademicYearController::class, 'destroy'])->name('destroy');
});
