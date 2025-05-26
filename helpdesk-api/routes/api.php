<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Auth;
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
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Protected Auth Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
    });
});

// Other Protected Routes (For Test)
Route::middleware('auth:sanctum')->group(function () {
    // Routes accessible by all authenticated users
    Route::get('/dashboard', function() {
        return response()->json([
            'status' => 'success',
            'message' => 'User dashboard',
            'data' => ['user_role' => Auth::user() ? Auth::user()->role : null]
        ]);
    });
    
    // Routes accessible only by admin users
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', function() {
            return response()->json([
                'status' => 'success',
                'message' => 'Admin dashboard',
                'data' => ['user_role' => Auth::user() ? Auth::user()->role : null]
            ]);
        });
        // User Management Endpoints
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/statistics', [UserController::class, 'statistics']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::patch('/users/{id}', [UserController::class, 'update']);
        Route::patch('/users/{id}/role', [UserController::class, 'updateRole']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        
        // Category & SubCategory Management Endpoints
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::post('/categories/{category}/sub-categories', [CategoryController::class, 'storeSubCategory']);
    });
    
    // Routes accessible by admin or disposisi users
    Route::middleware('role:admin,disposisi')->group(function () {
        Route::get('/staff/dashboard', function() {
            return response()->json([
                'status' => 'success',
                'message' => 'Staff dashboard',
                'data' => ['user_role' => Auth::user() ? Auth::user()->role : null]
            ]);
        });
    });
    
    // Category & SubCategory Management Endpoints (accessible by all authenticated users)
    Route::get('/categories', [CategoryController::class, 'index']);
    
    // Ticket Management Endpoints (all authenticated users, with role-based restrictions in controller)
    Route::post('/tickets', [TicketController::class, 'store']);
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::get('/tickets/statistics', [TicketController::class, 'statistics']);
    Route::get('/tickets/{id}', [TicketController::class, 'show']);
    Route::patch('/tickets/{id}/status', [TicketController::class, 'updateStatus']);
    Route::post('/tickets/{id}/assign', [TicketController::class, 'assign']);
    Route::post('/tickets/{id}/feedback', [TicketController::class, 'addFeedback']);
    Route::delete('/tickets/{id}', [TicketController::class, 'destroy']);
    Route::post('/tickets/{id}/restore', [TicketController::class, 'restore']);
    
    // Notification Management Endpoints (all authenticated users)
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications', [NotificationController::class, 'store']);
});
