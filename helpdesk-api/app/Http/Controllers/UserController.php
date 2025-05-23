<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // GET /users
    public function index()
    {
        $users = User::all();
        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    // GET /users/{id}
    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }
        return response()->json([
            'status' => 'success',
            'data' => $user
        ]);
    }

    // PATCH /users/{id}
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }
        $validated = $request->validate([
            'name' => 'string|nullable',
            'email' => 'email|nullable|unique:users,email,' . $id,
        ]);
        $user->update($validated);
        return response()->json([
            'status' => 'success',
            'data' => $user
        ]);
    }

    // PATCH /users/{id}/role
    public function updateRole(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }
        $validated = $request->validate([
            'role' => 'required|string|in:admin,disposisi,student',
        ]);
        $user->role = $validated['role'];
        $user->save();
        return response()->json([
            'status' => 'success',
            'data' => $user
        ]);
    }

    // DELETE /users/{id}
    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }
        $user->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ]);
    }

    // GET /users/statistics
    public function statistics()
    {
        $total = User::count();
        $byRole = User::select('role')
            ->selectRaw('count(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role');
        return response()->json([
            'status' => 'success',
            'data' => [
                'total' => $total,
                'by_role' => $byRole
            ]
        ]);
    }
}
