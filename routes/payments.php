<?php

use App\Http\Controllers\Employee\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments')->name('employee.payments.')->middleware('auth:employee')->group(function () {
    Route::get('/', [PaymentController::class, 'index'])->name('index');
    Route::get('/create', [PaymentController::class, 'create'])->name('create');
    Route::post('/', [PaymentController::class, 'store'])->name('store');
    Route::get('/{id}', [PaymentController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [PaymentController::class, 'edit'])->name('edit');
    Route::match(['put','patch'], '/{id}', [PaymentController::class, 'update'])->name('update');
    Route::delete('/{id}', [PaymentController::class, 'destroy'])->name('destroy');

    Route::get('/payments/{id}/add-instalment', [PaymentController::class, 'addInstalment'])->name('add-instalment');
    Route::post('/{id}/instalment', [PaymentController::class, 'storeInstalment'])->name('store-instalment');
});
