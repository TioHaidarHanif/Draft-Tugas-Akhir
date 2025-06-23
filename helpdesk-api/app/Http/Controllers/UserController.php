<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Ticket;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users with ticket statistics.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $users = User::withCount('tickets')->get();
        
        // Add ticket statistics to each user
        $users->each(function ($user) {
            $user->tickets_statistics = [
                'total' => $user->tickets_count,
                'open' => $user->tickets()->where('status', 'open')->count(),
                'closed' => $user->tickets()->where('status', 'closed')->count(),
                'in_progress' => $user->tickets()->where('status', 'in_progress')->count(),
            ];
        });
        
        return response()->json([
            'status' => 'success',
            'message' => 'Users retrieved successfully',
            'data' => $users
        ]);
    }

    /**
     * Display the specified user with ticket details.
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = User::with(['tickets' => function ($query) {
            $query->select('id', 'user_id', 'judul', 'status', 'created_at')
                  ->latest();
        }])->find($id);
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
                'code' => 404
            ], 404);
        }
        
        // Add URL to each ticket
        $user->tickets->transform(function ($ticket) {
            $ticket->url = url("/api/tickets/{$ticket->id}");
            return $ticket;
        });
        
        // Add ticket statistics to the user data
        $user->tickets_statistics = [
            'total' => $user->tickets->count(),
            'open' => $user->tickets->where('status', 'open')->count(),
            'closed' => $user->tickets->where('status', 'closed')->count(),
            'in_progress' => $user->tickets->where('status', 'in_progress')->count(),
        ];
        
        return response()->json([
            'status' => 'success',
            'message' => 'User retrieved successfully',
            'data' => $user
        ]);
    }

    /**
     * Update the specified user's information.
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
                'code' => 404
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users')->ignore($id),
            ],
            'password' => 'sometimes|string|min:8',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422);
        }
        
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }
        
        $user->save();
        
        // Log the user update activity
        ActivityLogService::logUserManagement('update', 'User profile updated: ' . $user->id);
        
        return response()->json([
            'status' => 'success',
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Update the specified user's role.
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateRole(Request $request, $id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
                'code' => 404
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'role' => ['required', 'string', Rule::in(['admin', 'student', 'disposisi'])],
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422);
        }
        
        $user->role = $request->role;
        $user->save();
        
        // Log the role update activity
        ActivityLogService::logUserManagement(
            'role_update', 
            'User role updated to ' . $request->role . ' for user: ' . $user->id
        );
        
        return response()->json([
            'status' => 'success',
            'message' => 'User role updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Remove the specified user from storage.
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
                'code' => 404
            ], 404);
        }
        
        // Check if user is the only admin
        if ($user->role === 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete the only admin user',
                    'code' => 422
                ], 422);
            }
        }
        
        $user->delete();
        
        // Log the user deletion activity
        ActivityLogService::logUserManagement('delete', 'User deleted: ' . $user->id);
        
        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Get user statistics.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        $totalUsers = User::count();
        $adminUsers = User::where('role', 'admin')->count();
        $studentUsers = User::where('role', 'student')->count();
        $disposisiUsers = User::where('role', 'disposisi')->count();
        
        $ticketsCreated = Ticket::count();
        $ticketsOpen = Ticket::where('status', 'open')->count();
        $ticketsClosed = Ticket::where('status', 'closed')->count();
        
        $usersByDate = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'message' => 'User statistics retrieved successfully',
            'data' => [
                'total_users' => $totalUsers,
                'admin_users' => $adminUsers,
                'student_users' => $studentUsers,
                'disposisi_users' => $disposisiUsers,
                'tickets_created' => $ticketsCreated,
                'tickets_open' => $ticketsOpen,
                'tickets_closed' => $ticketsClosed,
                'registration_trend' => $usersByDate
            ]
        ]);
    }
}