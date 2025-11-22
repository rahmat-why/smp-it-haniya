<?php

use App\Http\Controllers\Employee\ClassController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Classes management routes
|--------------------------------------------------------------------------
|
| Routes for managing classes under the employee area.
|
*/

Route::prefix('classes')->name('employee.classes.')->middleware('auth:employee')->group(function () {
    Route::get('/', [ClassController::class, 'index'])->name('index');
    Route::get('/data', [ClassController::class, 'getData'])->name('data');
    Route::get('/create', [ClassController::class, 'create'])->name('create');
    Route::post('/', [ClassController::class, 'store'])->name('store');
    Route::get('/get/{id}', [ClassController::class, 'getClass'])->name('get');
    Route::get('/edit/{id}', [ClassController::class, 'edit'])->name('edit');
    Route::put('/{id}', [ClassController::class, 'update'])->name('update');
    Route::get('/get-new-id', [ClassController::class, 'getNewClassId'])->name('getNewId');
    Route::delete('/{id}', [ClassController::class, 'destroy'])->name('destroy');
});
