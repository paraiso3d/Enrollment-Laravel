<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\sections;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Throwable;

class SectionsController extends Controller
{
    public function getSections()
    {
        try {
             $user = Auth::user();
            // Retrieve only non-archived sections
            $sections = sections::with(['instructor', 'course', 'schoolYear'])
                ->where('is_archived', 0)
                ->get()
                ->map(function ($section) {
                    return [
                        'id' => $section->id,
                        'section_name' => $section->section_name,
                        'course' => [
                            'id' => $section->course->id,
                            'name' => $section->course->course_name,
                        ],
                        'school_year' => [
                            'id' => $section->schoolYear->id,
                            'year' => $section->schoolYear->school_year,
                            'semester' => $section->schoolYear->semester,
                        ],
                        'instructor' => [
                            'id' => $section->instructor->id,
                            'name' => $section->instructor->first_name . ' ' . $section->instructor->last_name,
                        ],
                        'created_at' => $section->created_at,
                        'updated_at' => $section->updated_at,
                    ];
                });


            return response()->json([
                'isSuccess' => true,
                'sections' => $sections,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to retrieve sections.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

  public function addSection(Request $request)
{
    try {
        // Validate the request data
        $validated = $request->validate([
            'section_name' => 'required|string|max:100',
            'course_id' => 'required|exists:courses,id',
            'instructor_id' => 'required|exists:accounts,id',
        ]);

        // Check for duplicate section under same course
        $duplicate = sections::where('section_name', $validated['section_name'])
            ->where('course_id', $validated['course_id'])
            ->first();

        if ($duplicate) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Section already exists under this course.',
            ], 409); //
        }

        // Create the new section
        $section = sections::create([
            'section_name' => $validated['section_name'],
            'course_id' => $validated['course_id'],
            'instructor_id' => $validated['instructor_id'],
        ]);

        return response()->json([
            'isSuccess' => true,
            'message' => 'Section added successfully.',
            'section' => $section,
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
            'message' => 'Failed to add section.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    public function updateSection(Request $request, $id)
    {
        try {
            // Find the section by ID
            $section = sections::findOrFail($id);
             $user = Auth::user();
            // Validate the request data
            $validated = $request->validate([
                'section_name' => 'required|string|max:100',
                'course_id' => 'required|exists:courses,id',
                'school_year_id' => 'required|exists:school_years,id',
                'instructor_id' => 'required|exists:accounts,id',
            ]);

            // Update the section
            $section->update($validated);

            return response()->json([
                'isSuccess' => true,
                'message' => 'Section updated successfully.',
                'section' => $section,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Section not found.',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to update section.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteSection($id)
    {
        try {
             $user = Auth::user();
            // Find the section by ID
            $section = sections::findOrFail($id);

            // Archive the section instead of deleting it
            $section->is_archived = 1;
            $section->save();

            return response()->json([
                'isSuccess' => true,
                'message' => 'Section archived successfully.',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Section not found.',
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to archive section.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function restoreSection($id)
    {
        try {
             $user = Auth::user();
            // Find the section by ID
            $section = sections::findOrFail($id);

            // Restore the section
            $section->is_archived = 0;
            $section->save();

            return response()->json([
                'isSuccess' => true,
                'message' => 'Section restored successfully.',
                'section' => $section,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Section not found.',
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to restore section.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
