<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\accounts;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Support\Facades\Validator;

class AccountsController extends Controller
{

    // Method to register a new account
    public function createUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'school_campus' => 'required|string|max:255',
                'academic_year' => 'required|string|max:255',
                'application_type' => 'required|string|max:50',
                'classification' => 'required|string|max:50',
                'grade_level' => 'required|string|max:50',
                'academic_program' => 'required|string|max:255',
                'surname' => 'required|string|max:50',
                'given_name' => 'required|string|max:50',
                'middle_name' => 'nullable|string|max:50',
                'middle_initial' => 'nullable|string|max:5',
                'suffix' => 'nullable|string|max:10',
                'date_of_birth' => 'required|date',
                'place_of_birth' => 'required|string|max:100',
                'gender' => 'required|string|max:10',
                'civil_status' => 'required|string|max:20',
                'internet_connectivity' => 'required|string|max:50',
                'learning_modality' => 'required|string|max:50',
                'digital_literacy' => 'required|string|max:50',
                'device' => 'required|string|max:50',

                //  Dropdown inputs
                'street_address' => 'required|string|max:255',
                'province' => 'required|string|max:100',
                'city' => 'required|string|max:100',
                'barangay' => 'required|string|max:100',

                'nationality' => 'required|string|max:50',
                'religion' => 'required|string|max:50',
                'ethnic_affiliation' => 'nullable|string|max:50',
                'telephone_number' => 'nullable|string|max:15',
                'mobile_number' => 'required|string|max:15',
                'email' => 'required|email|max:100',
                'is_4ps_member' => 'required|boolean',
                'is_insurance_member' => 'required|boolean',
                'vacation_status' => 'required|string|max:50',
                'is_indigenous' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validatedData = $validator->validated();
            $plainPassword = Str::random(8); // you can use any length
            $verificationCode = rand(100000, 999999); // or use Str::uuid()

            // Step 2: Add to validated data
            $validatedData['password'] = Hash::make($plainPassword);
            $validatedData['is_verified'] = 0; // default not verified
            $validatedData['verification_code'] = $verificationCode; // make sure this column exists

            // Step 3: Save the account
            $account = accounts::create($validatedData);

            // Step 4: Send email
            Mail::raw("Welcome! Your password is: $plainPassword\nYour verification code is: $verificationCode", function ($message) use ($account) {
                $message->to($account->email)
                    ->subject('Your Account Details');
            });


            return response()->json([
                'isSuccess' => true,
                'message' => 'Admission created successfully.',
                'accounts' => $account,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to create admission.',
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
