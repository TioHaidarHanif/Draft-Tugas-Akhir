<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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
            // Add new fields with validation rules
            'nim' => 'string|max:30',
            'prodi' => 'string|max:100',
            'no_hp' => 'string|max:20',
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
            // Add new fields
            'nim' => $request->nim,
            'prodi' => $request->prodi,
            'no_hp' => $request->no_hp,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // Log the registration activity
        ActivityLogService::logAuth('register', 'User registered successfully', $user->id);

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
                // Log the failed login attempt
                ActivityLogService::logAuth('login_failed', 'Failed login attempt for email: ' . $request->email);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'The provided credentials are incorrect.',
                    'code' => 401
                ], 401);
            }

            $user = User::where('email', $request->email)->firstOrFail();
            $token = $user->createToken('auth_token')->plainTextToken;

            // Log the successful login activity
            ActivityLogService::logAuth('login', 'User logged in successfully', $user->id);

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
        // Get user ID before we delete the token
        $userId = $request->user()->id;
        
        $request->user()->currentAccessToken()->delete();

        // Log the logout activity
        ActivityLogService::logAuth('logout', 'User logged out successfully', $userId);

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
                'nim' => $user->nim,
                'prodi' => $user->prodi,
                'no_hp' => $user->no_hp,
                'created_at' => $user->created_at
            ]
        ]
    ]);
}
/**
 * Update user profile
 * 
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function updateProfile(Request $request)
{
    $user = Auth::user();
    
    $validator = Validator::make($request->all(), [
        'nim' => 'sometimes|string|max:30',
        'prodi' => 'sometimes|string|max:100',
        'no_hp' => 'sometimes|string|max:20',
    ]);
    
    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
            'code' => 422
        ], 422);
    }
    
    try {
        $user->update($request->only([
            'name', 'nim', 'nama', 'prodi', 'semester', 'no_hp'
        ]));
        
        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to update profile: ' . $e->getMessage(),
            'code' => 500
        ], 500);
    }
}
}
