<?php

use App\Http\Controllers\Employee\ArticleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Articles management routes
|--------------------------------------------------------------------------
|
| Routes for managing articles under the employee area.
|
*/

Route::prefix('articles')->name('employee.articles.')->middleware('auth:employee')->group(function () {
    Route::get('/', [ArticleController::class, 'index'])->name('index');
    Route::get('/data', [ArticleController::class, 'getData'])->name('data');
    Route::get('/create', [ArticleController::class, 'create'])->name('create');
    Route::post('/', [ArticleController::class, 'store'])->name('store');
    Route::get('/edit/{id}', [ArticleController::class, 'edit'])->name('edit');
    Route::put('/{id}', [ArticleController::class, 'update'])->name('update');
    Route::delete('/{id}', [ArticleController::class, 'destroy'])->name('destroy');

    // Tag routes
    Route::get('/{articleId}/tags', [ArticleController::class, 'indexTag'])->name('tag');
    Route::get('/{articleId}/tags/create', [ArticleController::class, 'createTag'])->name('create-tag');
    Route::post('/{articleId}/tags', [ArticleController::class, 'storeTag'])->name('store-tag');
    Route::delete('/{articleId}/tags/{tagId}', [ArticleController::class, 'destroyTag'])->name('destroy-tag');

    // API-style helpers if controller has them
    Route::get('/get-new-id', [ArticleController::class, 'getNewArticleId'])->name('getNewId');
});
