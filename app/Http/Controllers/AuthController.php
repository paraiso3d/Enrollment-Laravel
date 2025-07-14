<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\accounts;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'email' => 'required|string',
                'password' => 'required|string',
            ]);

            // Find user by email (you used 'username' but input is 'email')
            $user = accounts::where('email', $request->email)->first();

            // Check user exists and password is correct
            if ($user && Hash::check($request->password, $user->password)) {

                // Check if user is verified
                if (!$user->is_verified) {
                    return response()->json([
                        'isSuccess' => false,
                        'message' => 'Your account is not yet verified. Please check your email.',
                    ], 403); // 403 Forbidden
                }

                // Generate token
                $token = $user->createToken('auth-token')->plainTextToken;

                return response()->json([
                    'isSuccess' => true,
                    'message' => 'Logged in successfully',
                    'token' => $token,
                    'user' => $user->makeHidden(['password', 'created_at', 'updated_at']),
                ], 200);
            }

            return response()->json([
                'isSuccess' => false,
                'message' => 'Invalid Email or Password.',
            ], 401);
        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Login failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function logout(Request $request)
    {
        try {
            // Revoke the token
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'isSuccess' => true,
                'message' => 'Logged out successfully',
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Logout failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
