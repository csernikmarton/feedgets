<?php

use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FeedController;
use App\Http\Controllers\Api\V1\OpmlController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:6,1')->name('auth.register');
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1')->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::apiResource('feeds', FeedController::class);
        Route::post('feeds/{feed}/reorder', [FeedController::class, 'reorder'])->name('feeds.reorder');
        Route::get('feeds/{feed}/articles', [ArticleController::class, 'index'])->name('feeds.articles.index');
        Route::post('feeds/{feed}/mark-all-read', [ArticleController::class, 'markAllAsRead'])->name('feeds.mark-all-read');

        Route::get('articles', [ArticleController::class, 'index'])->name('articles.index');
        Route::get('articles/{article}', [ArticleController::class, 'show'])->name('articles.show');
        Route::post('articles/{article}/mark-read', [ArticleController::class, 'markAsRead'])->name('articles.mark-read');

        Route::post('opml/import', [OpmlController::class, 'import'])->name('opml.import');
        Route::get('opml/export', [OpmlController::class, 'export'])->name('opml.export');
    });
});
