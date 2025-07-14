<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\user_types;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Throwable;

class UserTypesController extends Controller
{
    public function getUserTypes()
    {
        
        try {
            // Retrieve all user types
            $userTypes = user_types::where('is_archived', 0)->get();

            return response()->json([
                'isSuccess' => true,
                'userTypes' => $userTypes,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to retrieve user types.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createUserType(Request $request)
    {
        try {
            
            // Validate the request data
            $validator = Validator::make($request->all(), [
                 'role_name' => 'required|string|max:255|unique:user_types,role_name',
                'description' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $user = Auth::user();
            // Create a new user type
            $userType = user_types::create([
                'role_name' => $request->role_name,
                'description' => $request->role_description,
            ]);

            return response()->json([
                'isSuccess' => true,
                'userType' => $userType,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Validation failed.',
                'errors' => $e->validator->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to create user type.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

public function updateUserType(Request $request, $id)
{
    try {
        $user = Auth::user();

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'role_name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('user_types', 'role_name')->ignore($id),
            ],
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Find the user type by ID
        $userType = user_types::findOrFail($id);

        // Update fields only if they exist
        if ($request->has('role_name')) {
            $userType->role_name = $request->role_name;
        }

        if ($request->has('description')) {
            $userType->description = $request->description;
        }

        $userType->save();

        return response()->json([
            'isSuccess' => true,
            'userType' => $userType,
        ], 200);
    } catch (ModelNotFoundException $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'User type not found.',
        ], 404);
    } catch (ValidationException $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Validation failed.',
            'errors' => $e->validator->errors(),
        ], 422);
    } catch (Throwable $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Failed to update user type.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


    public function deleteUserType($id)
    {
        try {
            $user = Auth::user();
            // Find the user type by ID
            $userType = user_types::findOrFail($id);

            // Archive the user type instead of deleting
            $userType->is_archived = 1;
            $userType->save();

            return response()->json([
                'isSuccess' => true,
                'message' => 'User type archived successfully.',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'User type not found.',
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to archive user type.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function restoreUserType($id)
    {
        try {
            $user = Auth::user();
            // Find the user type by ID
            $userType = user_types::where('id', $id)->where('is_archived', 1)->firstOrFail();

            // Restore the user type
            $userType->is_archived = 0;
            $userType->save();

            return response()->json([
                'isSuccess' => true,
                'message' => 'User type restored successfully.',
                'userType' => $userType,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'User type not found or not archived.',
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to restore user type.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
