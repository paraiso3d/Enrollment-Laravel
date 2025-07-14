<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\accounts;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class AccountsController extends Controller
{

    // Method to register a new account
    public function registerAccount(Request $request)
    {
        // Validate the request data
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:50',
                'last_name' => 'required|string|max:50',
                'password' => 'required|string|min:6',
                'email' => 'required|email|unique:accounts,email',
                'gender' => 'required|string',
                'contact_number' => 'required|string|max:15',
                'user_type_id' => 'nullable|string',
            ]);

            // Generate verification code
            $verificationCode = Str::upper(Str::random(6));

            // Create user
            $user = accounts::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'password' => Hash::make($validated['password']),
                'email' => $validated['email'],
                'gender' => $validated['gender'],
                'contact_number' => $validated['contact_number'] ?? null,
                'user_type_id' => $validated['user_type_id'] ?? 'student',
                'verification_code' => $verificationCode,
            ])->makeHidden(['password', 'created_at', 'updated_at']);

            // Raw HTML Email
            $html = "
            <html>
                <body style='font-family: Arial, sans-serif;'>
                    <h2>Hello, {$validated['first_name']}!</h2>
                    <p>Thank you for registering.</p>
                    <p>Your verification code is:</p>
                    <h1 style='color: #3498db;'>$verificationCode</h1>
                    <p>Please enter this code to verify your account.</p>
                    <br>
                    <small>This is an automated message. Do not reply.</small>
                </body>
            </html>
        ";

            Mail::send([], [], function ($message) use ($validated, $html) {
                $message->to($validated['email'])
                    ->subject('Account Verification Code')
                    ->setBody($html, 'text/html');
            });

            return response()->json([
                'isSuccess' => true,
                'message' => 'Account created successfully. Verification code sent to your email.',
                'user' => $user,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Account creation failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    // Method to verify the account using the verification code
    public function verifyAccount(Request $request)
    {
        try {
            $validated = $request->validate([
                'verification_code' => 'required|string',
            ]);

            // Find user by code
            $user = accounts::where('verification_code', $validated['verification_code'])->first();

            if (!$user) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Invalid or expired verification code.',
                ], 400);
            }

            $user->is_verified = true;
            $user->verification_code = null;
            $user->save();

            return response()->json([
                'isSuccess' => true,
                'message' => 'Account verified successfully.',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Verification failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Method to get the user profile
    public function getProfile(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'User not authenticated.',
                ], 401);
            }

            return response()->json([
                'isSuccess' => true,
                'user' => $user->makeHidden(['password', 'verification_code', 'is_verified', 'created_at', 'updated_at']),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to retrieve user profile.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Method to change the user profile
    public function changeProfile(Request $request)
    {
        try {
            $user = $request->user(); // Authenticated user

            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:50',
                'last_name' => 'sometimes|string|max:50',
                'gender' => 'sometimes|string|in:male,female,other',
                'contact_number' => 'sometimes|nullable|string|max:15',
                'email' => 'sometimes|email|unique:accounts,email,' . $user->id,
            ]);

            $user->update($validated);

            return response()->json([
                'isSuccess' => true,
                'message' => 'Profile updated successfully.',
                'user' => $user->makeHidden(['password', 'verification_code', 'is_verified', 'created_at', 'updated_at']),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Profile update failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $user = $request->user(); // Authenticated user

            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6|confirmed',
            ]);

            // Check current password
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Current password is incorrect.',
                ], 400);
            }

            // Update password
            $user->password = Hash::make($validated['new_password']);
            $user->save();

            return response()->json([
                'isSuccess' => true,
                'message' => 'Password changed successfully.',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Password change failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Method to delete the user account
    public function deleteAccount(Request $request)
    {
        try {
            $user = $request->user(); // Authenticated user
            if (!$user) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'User not authenticated.',
                ], 401);
            }
            // Archive the user instead of deleting
            $user->is_archive = 1;
            $user->save();
            return response()->json([
                'isSuccess' => true,
                'message' => 'Account archived successfully.',
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to archive account.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Method to restore the user account
    public function restoreAccount(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'User not authenticated.',
                ], 401);
            }
            // Restore the user account
            $user->is_archive = 0;
            $user->save();
            return response()->json([
                'isSuccess' => true,
                'message' => 'Account restored successfully.',
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to restore account.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function adminCreateAccount(Request $request)
{
    try {
        // Authenticate admin first (optional if already protected by middleware)
        $admin = $request->user();
        if (!$admin || $admin->user_type !== 'admin') {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Unauthorized. Only admins can create accounts.'
            ], 403);
        }

        // Validate input
        $validated = $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|unique:accounts,email',
            'gender' => 'required|string',
            'contact_number' => 'required|max:15',
           'user_type' => 'required|exists:user_types,id',
        ]);

        // Generate verification code and temporary password
        $verificationCode = Str::upper(Str::random(6));
        $tempPassword = Str::random(8); // You may email this to the user

        // Create account
        $user = accounts::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($tempPassword),
            'gender' => $validated['gender'],
            'contact_number' => $validated['contact_number'],
            'user_type' => $validated['user_type'],
            'verification_code' => $verificationCode,
        ])->makeHidden(['password', 'created_at', 'updated_at']);

        // Email HTML
        $html = "
            <html>
                <body style='font-family: Arial, sans-serif;'>
                    <h2>Hello, {$validated['first_name']}!</h2>
                    <p>An account has been created for you by the administrator.</p>
                    <p>Your temporary password is: <strong>$tempPassword</strong></p>
                    <p>Your verification code is:</p>
                    <h1 style='color: #3498db;'>$verificationCode</h1>
                    <p>Please log in, verify your account, and update your password.</p>
                    <br>
                    <small>This is an automated message. Do not reply.</small>
                </body>
            </html>
        ";

        // Send email
        Mail::send([], [], function ($message) use ($validated, $html) {
            $message->to($validated['email'])
                ->subject('Your Account Has Been Created')
                ->setBody($html, 'text/html');
        });

        return response()->json([
            'isSuccess' => true,
            'message' => 'Account created and email sent successfully.',
            'user' => $user,
        ], 201);

    } catch (ValidationException $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Throwable $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Account creation failed.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}
