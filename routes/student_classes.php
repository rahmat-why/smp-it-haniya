<?php

use App\Http\Controllers\Employee\StudentClassController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Student Classes management routes
|--------------------------------------------------------------------------
|
| Routes for managing student-class assignments under the employee area.
|
*/

Route::prefix('student-classes')->name('employee.student_classes.')->middleware('auth:employee')->group(function () {
    Route::get('/', [StudentClassController::class, 'index'])->name('index');
    Route::get('/data', [StudentClassController::class, 'getData'])->name('data');
    Route::get('/get-new-id', [StudentClassController::class, 'getNewStudentClassId'])->name('getNewId');
    Route::get('/create', [StudentClassController::class, 'create'])->name('create');
    Route::post('/', [StudentClassController::class, 'store'])->name('store');
    Route::get('/edit/{id}', [StudentClassController::class, 'edit'])->name('edit');
    Route::put('/{id}', [StudentClassController::class, 'update'])->name('update');
    Route::delete('/{id}', [StudentClassController::class, 'destroy'])->name('destroy');
});
