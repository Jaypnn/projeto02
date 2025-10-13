<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1');

    Route::post('/login', [AuthController::class, 'loginWithToken'])
        ->middleware('throttle:10,1');
    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum');

    // Fluxo de Cookies
    Route::post('/session/login', [AuthController::class, 'loginWithSession'])
        ->middleware('throttle:10,1');
    Route::post('/session/logout', [AuthController::class, 'logoutFromSession'])
        ->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    return $request->user();
});