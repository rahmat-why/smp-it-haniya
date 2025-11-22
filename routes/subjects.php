<?php

use App\Http\Controllers\Employee\SubjectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Subjects management routes
|--------------------------------------------------------------------------
|
| Routes for managing subjects under the employee area.
|
*/

Route::prefix('subjects')->name('employee.subjects.')->middleware('auth:employee')->group(function () {
    Route::get('/', [SubjectController::class, 'index'])->name('index');
    Route::get('/data', [SubjectController::class, 'getData'])->name('data');
    Route::get('/create', [SubjectController::class, 'create'])->name('create');
    Route::post('/', [SubjectController::class, 'store'])->name('store');
    Route::get('/get/{id}', [SubjectController::class, 'getSubject'])->name('get');
    Route::get('/edit/{id}', [SubjectController::class, 'edit'])->name('edit');
    Route::put('/{id}', [SubjectController::class, 'update'])->name('update');
    Route::get('/get-new-id', [SubjectController::class, 'getNewSubjectId'])->name('getNewId');
    Route::delete('/{id}', [SubjectController::class, 'destroy'])->name('destroy');
});
