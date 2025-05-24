<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
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

// Public Routes - No Authentication Required
// This ensures the categories index route is outside any auth middleware
Route::get('/categories', [CategoryController::class, 'index']);

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
            'data' => ['user_role' => auth()->user()->role]
        ]);
    });
    
    // Routes accessible only by admin users
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', function() {
            return response()->json([
                'status' => 'success',
                'message' => 'Admin dashboard',
                'data' => ['user_role' => auth()->user()->role]
            ]);
        });
        
        // User Management Routes (Admin only)
        Route::get('/users/statistics', [UserController::class, 'statistics']);
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::patch('/users/{id}', [UserController::class, 'update']);
        Route::patch('/users/{id}/role', [UserController::class, 'updateRole']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        
        // Category Management Routes (Admin only)
        // Note: GET /categories is defined as a public route above
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::get('/categories/{id}', [CategoryController::class, 'show']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
        
        // SubCategory Management Routes (Admin only)
        Route::post('/categories/{category_id}/sub-categories', [CategoryController::class, 'storeSubCategory']);
        Route::put('/categories/{category_id}/sub-categories/{subcategory_id}', [CategoryController::class, 'updateSubCategory']);
        Route::delete('/categories/{category_id}/sub-categories/{subcategory_id}', [CategoryController::class, 'destroySubCategory']);
    });
    
    // Routes accessible by admin or disposisi users
    Route::middleware('role:admin,disposisi')->group(function () {
        Route::get('/staff/dashboard', function() {
            return response()->json([
                'status' => 'success',
                'message' => 'Staff dashboard',
                'data' => ['user_role' => auth()->user()->role]
            ]);
        });
    });
    
    // Ticket Management Routes
    // Create ticket (all authenticated users)
    Route::post('/tickets', [TicketController::class, 'store']);
    
    // Get ticket list (role-based filtering applied in controller)
    Route::get('/tickets', [TicketController::class, 'index']);
    
    // Get ticket statistics (role-based filtering applied in controller)
    Route::get('/tickets/statistics', [TicketController::class, 'statistics']);
    
    // Get ticket details (authorization checked in controller)
    Route::get('/tickets/{id}', [TicketController::class, 'show']);
    
    // Update ticket status (authorization checked in controller)
    Route::patch('/tickets/{id}/status', [TicketController::class, 'updateStatus']);
    
    // Add feedback to ticket (authorization checked in controller)
    Route::post('/tickets/{id}/feedback', [TicketController::class, 'addFeedback']);
    
    // Soft delete ticket (authorization checked in controller)
    Route::delete('/tickets/{id}', [TicketController::class, 'destroy']);
    
    // Admin-only ticket routes
    Route::middleware('role:admin')->group(function () {
        // Assign ticket to disposisi
        Route::post('/tickets/{id}/assign', [TicketController::class, 'assign']);
        
        // Restore deleted ticket
        Route::post('/tickets/{id}/restore', [TicketController::class, 'restore']);
    });
});
