<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToGoogle()
    {
        // Check if we're in a web context with sessions or API context
        try {
            return Socialite::driver('google')->redirect();
        } catch (Exception $e) {
            // If session issue, try stateless
            return Socialite::driver('google')->stateless()->redirect();
        }
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleGoogleCallback()
    {
        try {
            // Try normal callback handling
            try {
                $googleUser = Socialite::driver('google')->user();
            } catch (Exception $e) {
                // If session issue, try stateless
                $googleUser = Socialite::driver('google')->stateless()->user();
            }
            
            // Check if user already exists
            $user = User::where('google_id', $googleUser->id)
                        ->orWhere('email', $googleUser->email)
                        ->first();

            // If user doesn't exist, create a new one
            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'google_token' => $googleUser->token,
                    'google_refresh_token' => $googleUser->refreshToken,
                    'avatar' => $googleUser->avatar,
                    'password' => null, // Password can be null for social login
                ]);
            } else {
                // If user exists but doesn't have Google ID, update with Google data
                $user->update([
                    'google_id' => $googleUser->id,
                    'google_token' => $googleUser->token,
                    'google_refresh_token' => $googleUser->refreshToken,
                    'avatar' => $googleUser->avatar,
                ]);
            }

            // Generate API token for the user
            $token = $user->createToken('Google-OAuth-Token')->accessToken;

            // Return a response with the token
            return redirect()->to('/auth/google/callback?token=' . $token);

        } catch (Exception $e) {
            return redirect()->to('/auth/google/callback?error=' . $e->getMessage());
        }
    }

    /**
     * Handle API login with Google
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginWithGoogle(Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                'id_token' => 'required|string',
            ]);

            // Get Google user info from the provided token
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->userFromToken($request->id_token);
            
            $user = User::where('google_id', $googleUser->id)
                        ->orWhere('email', $googleUser->email)
                        ->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'google_token' => $googleUser->token,
                    'google_refresh_token' => $googleUser->refreshToken,
                    'avatar' => $googleUser->avatar,
                    'password' => null,
                ]);
            } else {
                $user->update([
                    'google_id' => $googleUser->id,
                    'google_token' => $googleUser->token,
                    'google_refresh_token' => $googleUser->refreshToken,
                    'avatar' => $googleUser->avatar,
                ]);
            }

            $token = $user->createToken('Google-OAuth-Token')->accessToken;

            return response()->json([
                'token' => $token,
                'user' => $user
            ]);
            
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
