<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PremioController;
use App\Http\Controllers\PromocionController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/check-email', [AuthController::class, 'checkEmail']);
Route::post('/check-phone', [AuthController::class, 'checkPhone']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/promociones', [PromocionController::class, 'apiIndex']);
    Route::get('/premios', [PremioController::class, 'apiIndex']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Rutas de Google (ajusta según necesites)
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::post('login/google', [AuthController::class, 'loginWithGoogle']);