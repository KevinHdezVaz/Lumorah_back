<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/check-email', [AuthController::class, 'checkEmail']);
Route::post('/check-phone', [AuthController::class, 'checkPhone']);
Route::post('/google-login', [AuthController::class, 'googleLogin']);

// Rutas de Google
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::post('login/google', [AuthController::class, 'loginWithGoogle']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/update-name', [ChatController::class, 'updateUserName']);
    Route::post('/summarize', [ChatController::class, 'summarizeConversation']);
    
    Route::prefix('chat')->group(function () {
        Route::get('/sessions', [ChatController::class, 'getSessions']);
        Route::post('/sessions', [ChatController::class, 'saveChatSession']);
        Route::put('/sessions/{session}', [ChatController::class, 'saveSession']);
        Route::delete('/sessions/{session}', [ChatController::class, 'deleteSession']);
        Route::get('/sessions/{session}/messages', [ChatController::class, 'getSessionMessages']);
        Route::post('/send-message', [ChatController::class, 'sendMessage']);
        Route::post('/send-temporary-message', [ChatController::class, 'sendTemporaryMessage']);
        Route::post('/start-new-session', [ChatController::class, 'startNewSession']);
    });
});