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
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Cannot delete yourself
        if (auth()->id() === $user->id) {
            return response()->json([
                'message' => 'Cannot delete your own account',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}
