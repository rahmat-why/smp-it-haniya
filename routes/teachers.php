<?php

use App\Http\Controllers\Employee\TeacherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Teachers management routes
|--------------------------------------------------------------------------
|
| Routes for managing teachers under the employee area.
|
*/

Route::prefix('teachers')->name('employee.teachers.')->middleware('auth:employee')->group(function () {
    Route::get('/', [TeacherController::class, 'index'])->name('index');
    Route::get('/data', [TeacherController::class, 'getData'])->name('data');
    Route::get('/create', [TeacherController::class, 'create'])->name('create');
    Route::post('/', [TeacherController::class, 'store'])->name('store');
    Route::get('/get/{id}', [TeacherController::class, 'getTeacher'])->name('get');
    Route::get('/edit/{id}', [TeacherController::class, 'edit'])->name('edit');
    Route::put('/{id}', [TeacherController::class, 'update'])->name('update');
    Route::get('/get-new-id', [TeacherController::class, 'getNewTeacherId'])->name('getNewId');
    Route::delete('/{id}', [TeacherController::class, 'destroy'])->name('destroy');
});
