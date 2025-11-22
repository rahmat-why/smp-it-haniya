<?php

use App\Http\Controllers\Employee\EmployeeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Employees CRUD Routes
|--------------------------------------------------------------------------
|
| Kept only the employees management routes here. Other employee-related
| resource groups (students, teachers, classes, subjects) have been moved
| to their own files for better organization.
|
*/

Route::prefix('employees')->name('employee.employees.')->middleware('auth:employee')->group(function () {
    Route::get('/', [EmployeeController::class, 'index'])->name('index');
    Route::get('/data', [EmployeeController::class, 'getData'])->name('data');
    Route::get('/create', [EmployeeController::class, 'create'])->name('create');
    Route::post('/', [EmployeeController::class, 'store'])->name('store');
    Route::get('/get/{id}', [EmployeeController::class, 'getEmployee'])->name('get');
    Route::get('/edit/{id}', [EmployeeController::class, 'edit'])->name('edit');
    Route::put('/{id}', [EmployeeController::class, 'update'])->name('update');
    Route::get('/get-new-id', [EmployeeController::class, 'getNewEmployeeId'])->name('getNewId');

    Route::delete('/{id}', [EmployeeController::class, 'destroy'])->name('destroy');
});