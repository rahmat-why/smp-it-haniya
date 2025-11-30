<?php

use App\Http\Controllers\Employee\AttendanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Attendances management routes
|--------------------------------------------------------------------------
|
| Routes for recording and viewing attendances. Includes an API endpoint
| used by the attendance create page to load students for a selected class.
|
*/

// API endpoint (no employee prefix) used by frontend AJAX: /api/attendance/students/{classId}
Route::get('api/attendance/students/{classId}', [AttendanceController::class, 'getStudentsByClass']);

// Employee-area CRUD routes for attendances
Route::prefix('attendances')->name('employee.attendances.')->middleware('auth:employee,teacher')->group(function () {
    Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::get('/data', [AttendanceController::class, 'getData'])->name('data');

    Route::get('/create', [AttendanceController::class, 'create'])->name('create');
    Route::post('/', [AttendanceController::class, 'store'])->name('store');
    // Show by attendance id (header) to view details
    Route::get('/{attendanceId}', [AttendanceController::class, 'show'])->name('show');
    Route::delete('/{id}', [AttendanceController::class, 'destroy'])->name('destroy');
});
