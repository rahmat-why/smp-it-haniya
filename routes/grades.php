<?php

use App\Http\Controllers\Employee\GradeController;
use Illuminate\Support\Facades\Route;

// API endpoint for AJAX student loading
Route::get('api/grade/students/{classId}', [GradeController::class, 'getStudentsByClass']);

Route::prefix('grades')->name('employee.grades.')->middleware('auth:employee,teacher')->group(function () {
    Route::get('/', [GradeController::class, 'index'])->name('index');
    Route::get('/create', [GradeController::class, 'create'])->name('create');
    
    Route::get('/data', [GradeController::class, 'data'])->name('data'); // <- ini wajib

    Route::post('/', [GradeController::class, 'store'])->name('store');
    Route::get('/{id}', [GradeController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [GradeController::class, 'edit'])->name('edit');
    Route::match(['put','patch'],'/{id}', [GradeController::class, 'update'])->name('update');
    Route::delete('/{id}', [GradeController::class, 'destroy'])->name('destroy');
});
