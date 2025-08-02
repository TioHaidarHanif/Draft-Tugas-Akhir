<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\FAQController;
use App\Http\Controllers\NotificationController;
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

Route::get('/waw', function (){
   Artisan::call("storage:link");
   return "berhasil";
});
Route::get('/link', function () {        
   $target = '/home/public_html/storage/app/public';
   $shortcut = '/home/public_html/public/storage';
   symlink($target, $shortcut);
});
// Public Routes - No Authentication Required
// This ensures the categories index route is outside any auth middleware
Route::get('/categories', [CategoryController::class, 'index']);

// FAQ Public Routes
Route::get('/faqs/categories', [FAQController::class, 'categories']);
Route::get('/faqs', [FAQController::class, 'index']);
Route::get('/faqs/{id}', [FAQController::class, 'show']);

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Protected Auth Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/profile', [AuthController::class, 'updateProfile']);

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
    Route::get('/tickets/export', [TicketController::class, 'export']);

    // Get ticket details (authorization checked in controller)
    Route::get('/tickets/{id}', [TicketController::class, 'show']);
    
    // Reveal token for anonymous ticket (password verification in controller)
    Route::post('/tickets/{id}/reveal-token', [TicketController::class, 'revealToken']);
    
    // Update ticket status (authorization checked in controller)
    Route::patch('/tickets/{id}/status', [TicketController::class, 'updateStatus']);
    
    // Update ticket priority (authorization checked in controller)
    Route::patch('/tickets/{id}/priority', [TicketController::class, 'updatePriority']);
    
    // Add feedback to ticket (authorization checked in controller)
    Route::post('/tickets/{id}/feedback', [TicketController::class, 'addFeedback']);
    
    Route::delete('/tickets/{id}', [TicketController::class, 'destroy']);
    
   
    // Notification Routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications', [NotificationController::class, 'store']);
    
    // Chat Routes
    Route::get('/tickets/{id}/chat', [ChatController::class, 'index']);
    Route::post('/tickets/{id}/chat', [ChatController::class, 'store']);
    Route::delete('/tickets/{id}/chat/{message_id}', [ChatController::class, 'destroy']);
    Route::post('/tickets/{id}/chat/attachment', [ChatController::class, 'uploadAttachment']);
    Route::get('/tickets/{id}/chat/attachments', [ChatController::class, 'getAttachments']);
    
    // FAQ Management Routes (Admin only)
    Route::middleware('role:admin')->group(function () {
    Route::prefix('admin')->group(function () {
        Route::get('/faqs', [FAQController::class, 'indexAdmin']);
Route::get('/faqs/{id}', [FAQController::class, 'showAdmin']);
    });
        Route::post('/faqs', [FAQController::class, 'store']);
        Route::patch('/faqs/{id}', [FAQController::class, 'update']);
        Route::delete('/faqs/{id}', [FAQController::class, 'destroy']);
        Route::post('/tickets/{id}/convert-to-faq', [FAQController::class, 'convertTicketToFAQ']);
        // Email Management Routes (Admin only)
        Route::post('/emails/send', [EmailController::class, 'send']);
        Route::get('/emails/logs', [EmailController::class, 'logs']);
        
        // Activity Log Routes (Admin only)
        Route::get('/activity-logs', [ActivityLogController::class, 'index']);
        Route::get('/activity-logs/statistics', [ActivityLogController::class, 'statistics']);
        Route::get('/activity-logs/{id}', [ActivityLogController::class, 'show']);
    });
});
