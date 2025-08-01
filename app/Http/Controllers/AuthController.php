<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use App\Models\accounts;

class AuthController extends Controller
{
   public function login(Request $request)
{
    try {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        // Find user with userType relation
        $user = accounts::with('userType')
            ->where('email', $request->login)
            ->orWhere('username', $request->login)
            ->first();

        if ($user && Hash::check($request->password, $user->password)) {
            $token = $user->createToken('auth-token')->plainTextToken;

            // Format the response
            $userData = $user->makeHidden(['password', 'created_at', 'updated_at']);
            $userData['role_name'] = $user->userType->role_name ?? null;

            return response()->json([
                'isSuccess' => true,
                'message' => 'Logged in successfully',
                'token' => $token,
                'user' => $userData,
            ], 200);
        }

        return response()->json([
            'isSuccess' => false,
            'message' => 'Invalid Email/Username or Password.',
        ], 401);

    } catch (ValidationException $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors(),
        ], 422);
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
