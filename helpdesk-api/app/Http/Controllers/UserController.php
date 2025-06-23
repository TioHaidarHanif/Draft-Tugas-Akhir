<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use App\Services\ActivityLogService;

class UserController extends Controller
{
    // GET /users
    public function index()
    {
        // Eager load tickets untuk menghindari N+1
        $users = User::with(['tickets:id,user_id,judul,status'])->get();
        $data = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                // Statistik jumlah ticket
                'ticket_count' => $user->tickets->count(),
                // Daftar ticket (id, judul, status)
                'tickets' => $user->tickets->map(function ($ticket) {
                    return [
                        'id' => $ticket->id,
                        'judul' => $ticket->judul,
                        'status' => $ticket->status,
                    ];
                }),
            ];
        });
        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    // GET /users/{id}
    public function show($id)
    {
        $user = User::with(['tickets:id,user_id,judul,status'])->find($id);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'ticket_count' => $user->tickets->count(),
            'tickets' => $user->tickets->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'judul' => $ticket->judul,
                    'status' => $ticket->status,
                    'url' => '/tickets/' . $ticket->id,
                ];
            }),
        ];
        return response()->json([
            'status' => 'success',
            'data' => $data
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

        // Log aktivitas update profil
        ActivityLogService::log(
            $user->id,
            'update_profile',
            'User updated profile',
            $request->ip(),
            $request->userAgent()
        );

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

        // Log aktivitas update role
        ActivityLogService::log(
            $user->id,
            'update_role',
            'User role updated to ' . $user->role,
            $request->ip(),
            $request->userAgent()
        );

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
        // Log aktivitas hapus user (sebelum dihapus)
        ActivityLogService::log(
            $user->id,
            'delete_user',
            'User deleted',
            request()->ip(),
            request()->userAgent()
        );
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
