<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\school_years;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Throwable;

class SchoolYearsController extends Controller
{
    public function getSchoolYears()
    {
        try {
            // Retrieve all school years
            $schoolYears = school_years::where('is_archived', 0)->get();

            return response()->json([
                'isSuccess' => true,
                'schoolYears' => $schoolYears,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to retrieve school years.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createSchoolYear(Request $request)
    {
        try {
             $user = Auth::user();
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'school_year' => 'required|string|max:255',
                'semester' => 'required|string|max:50',
                'enrollment_start_date' => 'required|date',
                'enrollment_end_date' => 'sometimes|date|after_or_equal:enrollment_start_date',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
            $enrollmentEnd = $request->enrollment_end_date ?? 
            date('Y-m-d', strtotime($request->enrollment_start_date . ' +7 days'));

            // Create a new school year
            $schoolYear = school_years::create([
                'school_year' => $request->school_year,
                'semester' => $request->semester,
                'enrollment_start_date' => $request->enrollment_start_date,
                'enrollment_end_date' => $enrollmentEnd
            ]);

            return response()->json([
                'isSuccess' => true,
                'schoolYear' => $schoolYear,
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
                'message' => 'Failed to create school year.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateSchoolYear(Request $request, $id)
    {
        try {
            // Validate the request data
             $user = Auth::user();
            $validator = Validator::make($request->all(), [
                'school_year' => 'sometimes|string|max:255',
                'semester' => 'sometimes|string|max:50',
                'enrollment_start_date' => 'sometimes|date',
                'enrollment_end_date' => 'sometimes|date|after_or_equal:enrollment_start_date',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            // Find the school year by ID
            $schoolYear = school_years::findOrFail($id);

            // Update the school year
            $schoolYear->update($request->only(['school_year', 'semester', 'enrollment_start_date', 'enrollment_end_date']));

            return response()->json([
                'isSuccess' => true,
                'schoolYear' => $schoolYear,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Validation failed.',
                'errors' => $e->validator->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to update school year.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteSchoolYear($id)
    {
        try {
             $user = Auth::user();
            // Find the school year by ID
            $schoolYear = school_years::findOrFail($id);

            // Archive the school year
            $schoolYear->update(['is_archived' => 1]);

            return response()->json([
                'isSuccess' => true,
                'message' => 'School year archived successfully.',
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to archive school year.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function restoreSchoolYear($id)
    {
        try {
            // Find the school year by 
             $user = Auth::user();
            $schoolYear = school_years::findOrFail($id);

            // Restore the school year
            $schoolYear->update(['is_archived' => 0]);

            return response()->json([
                'isSuccess' => true,
                'message' => 'School year restored successfully.',
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to restore school year.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
