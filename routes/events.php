<?php

use App\Http\Controllers\Employee\EventController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Events management routes
|--------------------------------------------------------------------------
|
| Routes for managing events under the employee area.
|
*/

Route::prefix('events')->name('employee.events.')->middleware('auth:employee')->group(function () {
    Route::get('/', [EventController::class, 'index'])->name('index');
    Route::get('/data', [EventController::class, 'getData'])->name('data');
    Route::get('/create', [EventController::class, 'create'])->name('create');
    Route::post('/', [EventController::class, 'store'])->name('store');
    Route::get('/edit/{id}', [EventController::class, 'edit'])->name('edit');
    Route::put('/{id}', [EventController::class, 'update'])->name('update');
    Route::delete('/{id}', [EventController::class, 'destroy'])->name('destroy');

    // Tag routes
    Route::get('/{eventId}/tags', [EventController::class, 'indexTag'])->name('tag');
    Route::get('/{eventId}/tags/create', [EventController::class, 'createTag'])->name('create-tag');
    Route::post('/{eventId}/tags', [EventController::class, 'storeTag'])->name('store-tag');
    Route::delete('/{eventId}/tags/{tagId}', [EventController::class, 'destroyTag'])->name('destroy-tag');

    // API-style helpers if controller has them
    Route::get('/get-new-id', [EventController::class, 'getNewEventId'])->name('getNewId');
});
