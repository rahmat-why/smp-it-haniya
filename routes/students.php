<?php

use App\Http\Controllers\Employee\StudentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Students management routes
|--------------------------------------------------------------------------
|
| Routes for managing students under the employee area.
|
*/

Route::prefix('students')->name('employee.students.')->middleware('auth:employee,teacher')->group(function () {
    Route::get('/', [StudentController::class, 'index'])->name('index');
    Route::get('/data', [StudentController::class, 'getData'])->name('data');
    Route::get('/create', [StudentController::class, 'create'])->name('create');
    Route::post('/', [StudentController::class, 'store'])->name('store');
    Route::get('/get/{id}', [StudentController::class, 'getStudent'])->name('get');
    Route::get('/edit/{id}', [StudentController::class, 'edit'])->name('edit');
    Route::put('/{id}', [StudentController::class, 'update'])->name('update');
    Route::get('/get-new-id', [StudentController::class, 'getNewStudentId'])->name('getNewId');
    Route::delete('/{id}', [StudentController::class, 'destroy'])->name('destroy');
});
