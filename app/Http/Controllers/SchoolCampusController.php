<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\school_campus;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Throwable;

class SchoolCampusController extends Controller
{
    public function getCampuses()
    {
        try {
             $user = Auth::user();
            // Retrieve all campuses
            $campuses = school_campus::all();

            return response()->json([
                'isSuccess' => true,
                'campuses' => $campuses,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to retrieve campuses.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function addCampus(Request $request)
    {
        try {
             $user = Auth::user();
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'campus_name' => 'required|string|max:255',
                'campus_description' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            // Create a new campus
            $campus = school_campus::create($request->all());

            return response()->json([
                'isSuccess' => true,
                'message' => 'Campus created successfully.',
                'campus' => $campus,
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
                'message' => 'Failed to create campus.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
