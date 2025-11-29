<?php

use App\Http\Controllers\Employee\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix('schedules')->name('employee.schedules.')->middleware('auth:employee,teacher')->group(function () {
    Route::get('/', [ScheduleController::class, 'index'])->name('index');
            Route::get('/data', [ScheduleController::class, 'getData'])->name('data');

    Route::get('/create', [ScheduleController::class, 'create'])->name('create');
    Route::post('/', [ScheduleController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [ScheduleController::class, 'edit'])->name('edit');
    Route::match(['put','patch'],'/{id}', [ScheduleController::class, 'update'])->name('update');
    Route::delete('/{id}', [ScheduleController::class, 'destroy'])->name('destroy');
});
