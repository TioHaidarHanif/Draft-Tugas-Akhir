<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication Routes
Route::group(['prefix' => 'auth'], function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
                Route::get('/user', [AuthController::class, 'user']);

        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::patch('/profile', [AuthController::class, 'updateProfile']);
        
    });


    
});
    
    // Protected Routes

    Route::middleware('auth:sanctum')->group(function () {
        // Auth
    // User Management (Admin Only)
    Route::apiResource('users', UserController::class);
    Route::patch('/users/{user}/role', [UserController::class, 'updateRole']);
    Route::get('/users/statistics', [UserController::class, 'statistics']);
    
    // Ticket Management
    Route::apiResource('tickets', TicketController::class);
    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus']);
    Route::post('/tickets/{ticket}/assign', [TicketController::class, 'assignTicket']);
    Route::post('/tickets/{ticket}/comment', [TicketController::class, 'addComment']);
    Route::get('/tickets/statistics', [TicketController::class, 'statistics']);
    Route::patch('/tickets/{ticket}/restore', [TicketController::class, 'restore']);
    
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
});
