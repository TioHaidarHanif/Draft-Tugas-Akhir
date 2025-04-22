<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');
Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])
    ->middleware('auth:api');
Route::middleware('auth:api' )->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::middleware('role:admin')->group(function () {
        
        Route::get('/users/search', [\App\Http\Controllers\Api\UserController::class, 'search']);
        Route::get('/users/list', [\App\Http\Controllers\Api\UserController::class, 'list']);
        Route::get('/users', [\App\Http\Controllers\Api\UserController::class, 'index']);
        Route::get('/users/{id}', [\App\Http\Controllers\Api\UserController::class, 'show']);
        Route::put('/users/{id}', [\App\Http\Controllers\Api\UserController::class, 'update']);
        Route::delete('/users/{id}', [\App\Http\Controllers\Api\UserController::class, 'destroy']);
        Route::post('/users/{user}/roles', [\App\Http\Controllers\Api\UserController::class, 'assignRole']);
        Route::delete('/users/{user}/roles', [\App\Http\Controllers\Api\UserController::class, 'removeRole']);
    });
});
Route::get('/test', function () {
    return response()->json(['message' => 'Hello, World!']);
});
