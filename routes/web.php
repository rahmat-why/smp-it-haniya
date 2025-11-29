<?php

use App\Http\Controllers\Auth\EmployeeLoginController;
use App\Http\Controllers\Auth\TeacherLoginController;
use App\Http\Controllers\Auth\StudentLoginController;
use App\Http\Controllers\Employee\EmployeeController;
use App\Http\Controllers\Employee\TeacherController;
use App\Http\Controllers\Employee\StudentController;
use App\Http\Controllers\Employee\SubjectController;
use App\Http\Controllers\Employee\AcademicYearController;
use App\Http\Controllers\Employee\ClassController;
use App\Http\Controllers\Employee\StudentClassController;
use App\Http\Controllers\Employee\SettingController;
use App\Http\Controllers\Employee\ArticleController;
use App\Http\Controllers\Employee\EventController;
use App\Http\Controllers\Employee\AttendanceController;
use App\Http\Controllers\Employee\GradeController;
use App\Http\Controllers\Employee\ScheduleController;
use App\Http\Controllers\Employee\PaymentController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboard;
use App\Http\Controllers\Student\DashboardController as StudentDashboard;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

/**
 * ============================================================================
 * AUTHENTICATION ROUTES
 * ============================================================================
 * 
 * Three separate login systems for different user types:
 * - Employee (Admin/Staff)
 * - Teacher
 * - Student
 */

// Employee Authentication Routes
require __DIR__ . '/employees.php';
// Include separated employee-area resource routes
require __DIR__ . '/students.php';
require __DIR__ . '/teachers.php';
require __DIR__ . '/classes.php';
require __DIR__ . '/subjects.php';
require __DIR__ . '/academic_years.php';
require __DIR__ . '/academic_classes.php';
require __DIR__ . '/student_classes.php';
require __DIR__ . '/attendances.php';
require __DIR__ . '/events.php';
require __DIR__ . '/articles.php';
require __DIR__ . '/schedules.php';
require __DIR__ . '/grades.php';
require __DIR__ . '/payments.php';

Route::prefix('employee')->name('employee.')->group(function () {
    Route::get('/login', [EmployeeLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [EmployeeLoginController::class, 'authenticate'])->name('authenticate');
    Route::get('/logout', [EmployeeLoginController::class, 'logout'])->name('logout');
    
    // Employee Dashboard & CRUD
    Route::middleware('web')->group(function () {
        Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    });
});

// Teacher Authentication Routes
Route::prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/login', [TeacherLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [TeacherLoginController::class, 'authenticate'])->name('authenticate');
    Route::get('/logout', [TeacherLoginController::class, 'logout'])->name('logout');
    
    // Teacher Dashboard
    Route::middleware('web')->group(function () {
        Route::get('/dashboard', [TeacherDashboard::class, 'index'])->name('dashboard');
    });
});

// Student Authentication Routes
Route::prefix('student')->name('student.')->group(function () {
    Route::get('/login', [StudentLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [StudentLoginController::class, 'authenticate'])->name('authenticate');
    Route::get('/logout', [StudentLoginController::class, 'logout'])->name('logout');
    
    // Student Dashboard
    Route::middleware('web')->group(function () {
        Route::get('/dashboard', [StudentDashboard::class, 'index'])->name('dashboard');
        Route::get('/profile', [StudentController::class, 'profile'])->name('profile');
        Route::post('/profile/update', [StudentDashboard::class, 'updateProfile'])->name('profile.update');
    });
});
