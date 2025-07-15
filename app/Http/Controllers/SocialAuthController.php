<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\accounts;
use App\Models\admissions;
use Illuminate\Support\Str;


class SocialAuthController extends Controller
{
   public function redirectToGoogle()
    {
        /** @var \Laravel\Socialite\Two\GoogleProvider $googleDriver */
        $googleDriver = Socialite::driver('google');

        return $googleDriver->stateless()->redirect();
    }
    public function handleGoogleCallback()
{
    try {
        /** @var \Laravel\Socialite\Two\GoogleProvider $googleDriver */
        $googleDriver = Socialite::driver('google');
        
        // ✅ Get user details from Google
        $googleUser = $googleDriver->stateless()->user();

        // Check if user already exists
        $user = accounts::where('email', $googleUser->getEmail())->first();

        if (!$user) {
            // If user doesn't exist, create new account
            $user = accounts::create([
                'email' => $googleUser->getEmail(),
                'username' => $googleUser->getNickname() ?? Str::slug($googleUser->getName()),
                'password' => Hash::make(Str::random(12)), // dummy password
                'is_verified' => 1,
                'first_name' => $googleUser->user['given_name'] ?? '',
                'last_name' => $googleUser->user['surname'] ?? '',
            ]);
        }

        // Generate token
        $token = $user->createToken('google-token')->plainTextToken;

        return response()->json([
            'isSuccess' => true,
            'message' => 'Google login successful',
            'token' => $token,
            'user' => $user->makeHidden(['password', 'created_at', 'updated_at']),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Google login failed.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
    public function redirectToGithub()
    {
        return Socialite::driver('github')->redirect();
    }

    public function handleGithubCallback()
    {
        try {
            $githubUser = Socialite::driver('github')->user();

            $user = accounts::where('email', $githubUser->getEmail())->first();

            if (!$user) {
                $user = accounts::create([
                    'email' => $githubUser->getEmail(),
                    'username' => $githubUser->getNickname() ?? Str::slug($githubUser->getName()),
                    'password' => Hash::make(Str::random(12)),
                    'first_name' => $githubUser->getName() ?? '',
                    'is_verified' => 1,
                ]);
            }

            $token = $user->createToken('github-token')->plainTextToken;

            return response()->json([
                'isSuccess' => true,
                'message' => 'GitHub login successful',
                'token' => $token,
                'user' => $user->makeHidden(['password', 'created_at', 'updated_at']),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'GitHub login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



}

