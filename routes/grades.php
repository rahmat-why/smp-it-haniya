<?php

use App\Http\Controllers\Employee\GradeController;
use Illuminate\Support\Facades\Route;

Route::prefix('grade')->name('employee.grade.')->middleware('auth:employee')->group(function () {
    Route::get('/', [GradeController::class, 'index'])->name('index');
    Route::get('/create', [GradeController::class, 'create'])->name('create');
    Route::post('/', [GradeController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [GradeController::class, 'edit'])->name('edit');
    Route::match(['put','patch'],'/{id}', [GradeController::class, 'update'])->name('update');
    Route::delete('/{id}', [GradeController::class, 'destroy'])->name('destroy');
});
