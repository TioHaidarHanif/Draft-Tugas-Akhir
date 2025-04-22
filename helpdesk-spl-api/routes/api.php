<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');
Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])
    ->middleware('auth:api');

// Google OAuth Routes with web middleware for session support
Route::middleware(['web'])->group(function () {
    Route::get('auth/google', [GoogleController::class, 'redirectToGoogle']);
    Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);
});

// API version of Google login doesn't need sessions
Route::post('auth/google/login', [GoogleController::class, 'loginWithGoogle']);

Route::middleware('auth:api')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
Route::get('/test', function () {
    return response()->json(['message' => 'Hello, World!']);
});
