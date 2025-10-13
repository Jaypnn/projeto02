<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

// Rotas de teste para autenticação por sessão
Route::post('/web-auth/login', [AuthController::class, 'loginWithSession']);
Route::post('/web-auth/logout', [AuthController::class, 'logoutFromSession'])->middleware('auth:sanctum');
Route::get('/web-auth/me', function () {
    return response()->json(auth()->user());
})->middleware('auth:sanctum');
