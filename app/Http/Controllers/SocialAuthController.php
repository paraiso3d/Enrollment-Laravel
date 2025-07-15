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

        return $googleDriver->stateless()->redirect();

            // Check if user already exists
            $user = admissions::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // If user doesn't exist, create new account
                $user = admissions::create([
                    'email' => $googleUser->getEmail(),
                    'username' => $googleUser->getNickname() ?? Str::slug($googleUser->getName()),
                    'password' => Hash::make(Str::random(12)), // dummy password
                    'is_verified' => 1, // Mark social accounts as verified
                    'first_name' => $googleUser->user['given_name'] ?? '',
                    'last_name' => $googleUser->user['family_name'] ?? '',
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
}

