<?php

use App\Http\Controllers\Api\V1\PublicApiController;
use Illuminate\Support\Facades\Route;

// Public, read-only API v1 (future client portal / partners). Throttled per IP.
Route::prefix('v1')->middleware('throttle:120,60')->name('api.')->group(function () {
    Route::get('expertises', [PublicApiController::class, 'expertises'])->name('expertises');
    Route::get('solutions', [PublicApiController::class, 'solutions'])->name('solutions');
    Route::get('solutions/{slug}', [PublicApiController::class, 'solution'])->name('solution');
    Route::get('articles', [PublicApiController::class, 'articles'])->name('articles');
    Route::get('statut', [PublicApiController::class, 'status'])->name('status');
});
