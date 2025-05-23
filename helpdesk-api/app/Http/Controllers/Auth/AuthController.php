<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    'unique:users',
                    function ($attribute, $value, $fail) {
                        $allowedDomains = [
                            'student.telkomuniversity.ac.id',
                            'telkomuniversity.ac.id',
                            'adminhelpdesk.ac.id'
                        ];
                        
                        $domain = substr(strrchr($value, "@"), 1);
                        if (!in_array($domain, $allowedDomains)) {
                            $fail('Email must be a valid Telkom University email address');
                        }
                    },
                ],
                'password' => 'required|string|min:6|confirmed',
            ]);
            
            // Default role is 'student' unless specified and authorized
            $role = 'student';
            if ($request->has('role') && in_array($request->role, ['admin', 'disposisi'])) {
                // Only admin can create admin or disposisi accounts
                if (Auth::check() && Auth::user()->role === 'admin') {
                    $role = $request->role;
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Unauthorized to create this role',
                        'code' => 403
                    ], 403);
                }
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $role,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'User registered successfully',
                'token' => $token,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Registration failed',
                'errors' => $e->errors(),
                'code' => 422
            ], 422);
        }
    }

    /**
     * Login a user and generate token.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The provided credentials are incorrect.',
                    'code' => 401
                ], 401);
            }

            $user = User::where('email', $request->email)->firstOrFail();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role
                    ],
                    'token' => $token,
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Login failed',
                'errors' => $e->errors(),
                'code' => 422
            ], 422);
        }
    }

    /**
     * Logout a user by deleting the token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'created_at' => $user->created_at
                ]
            ]
        ]);
    }
}
