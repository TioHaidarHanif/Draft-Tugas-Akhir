<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Return the authenticated user
        return response()->json($request->user());
    }

    public function update(Request $request)
    {
        // Validate and update user information
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user = $request->user();
        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        return response()->json(['message' => 'User updated successfully']);
    }
    public function destroy(Request $request)
    {
        // Delete the authenticated user
        $user = $request->user();
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
    public function show(Request $request, $id)
    {
        // Show user by ID
        $user = User::findOrFail($id);
        return response()->json($user);
    }
    public function list(Request $request)
    {
        // List all users
        $users = User::all();
        
        return response()->json($users);
    }
    public function search(Request $request)
    {
        // Search users by name or email
        $query = $request->input('query');
        $users = User::where('name', 'LIKE', "%$query%")
            ->orWhere('email', 'LIKE', "%$query%")
            ->get();

        return response()->json($users);
    }

    public function assignRole(Request $request, User $user)
    {
        
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);
        $role = Role::where('name', $request->role)->first();
        $user->roles()->syncWithoutDetaching([$role->id]);
        return response()->json(['message' => 'Role assigned successfully', 'role' => $role->name]);

    }
    public function revokeRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);
        $role = Role::where('name', $request->role)->first();
        $user->roles()->detach($role->id);
        return response()->json(['message' => 'Role revoked successfully']);
    }
}
