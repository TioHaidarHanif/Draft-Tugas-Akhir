<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Only admin can list all users
        if (auth()->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        $users = User::all();
        
        return response()->json([
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created user in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Only admin can create new users
        if (auth()->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,user,disposisi',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
        ], 201);
    }

    /**
     * Display the specified user.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function show(User $user): JsonResponse
    {
        // Users can only view their own profile, admins can view any
        if (auth()->user()->role !== 'admin' && auth()->id() !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Update the specified user in storage.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function update(Request $request, User $user): JsonResponse
    {
        // Users can only update their own profile, admins can update any
        if (auth()->user()->role !== 'admin' && auth()->id() !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Role can only be changed by admin
        if (auth()->user()->role !== 'admin' && $request->has('role')) {
            return response()->json([
                'message' => 'Unauthorized to change role',
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|in:admin,user,disposisi',
        ]);

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('email')) {
            $user->email = $request->email;
        }

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        if ($request->has('role') && auth()->user()->role === 'admin') {
            $user->role = $request->role;
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ]);
    }

    /**
     * Remove the specified user from storage.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function destroy(User $user): JsonResponse
    {
        // Only admin can delete users
        if (auth()->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Cannot delete yourself
        if (auth()->id() === $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete your own account',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Update user role.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function updateRole(Request $request, User $user): JsonResponse
    {
        // Only admin can update roles
        if (auth()->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
            ], 403);
        }

        $request->validate([
            'role' => 'required|in:admin,user,disposisi',
        ]);

        // Cannot change your own role
        if (auth()->id() === $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot change your own role',
            ], 403);
        }

        $user->role = $request->role;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'User role updated successfully',
            'data' => [
                'user' => $user,
            ]
        ]);
    }

    /**
     * Get user statistics (for admin).
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        // Only admin can view statistics
        if (auth()->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Get counts by role
        $totalUsers = User::count();
        $adminUsers = User::where('role', 'admin')->count();
        $regularUsers = User::where('role', 'user')->count();
        $disposisiUsers = User::where('role', 'disposisi')->count();

        // Get recent users (last 30 days)
        $recentUsers = User::where('created_at', '>=', now()->subDays(30))->count();

        // Get active users (with tickets in last 30 days)
        $activeUsers = User::whereHas('tickets', function ($query) {
            $query->where('created_at', '>=', now()->subDays(30));
        })->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_users' => $totalUsers,
                'admin_users' => $adminUsers,
                'regular_users' => $regularUsers,
                'disposisi_users' => $disposisiUsers,
                'recent_users' => $recentUsers,
                'active_users' => $activeUsers,
            ]
        ]);
    }
}
