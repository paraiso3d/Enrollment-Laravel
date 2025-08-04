<?php

namespace App\Http\Controllers;

use App\Models\courses;
use App\Models\school_campus;
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
        if (!$user) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        // Retrieve only non-archived sections
        $sections = sections::with(['course', 'schoolYear'])
            ->where('is_archived', 0)
            ->get()
            ->map(function ($section) {
                return [
                    'id' => $section->id,
                    'section_name' => $section->section_name,
                    'course' => $section->course ? [
                        'id' => $section->course->id,
                        'name' => $section->course->course_name,
                    ] : null,
                    'school_year' => $section->schoolYear ? [
                        'id' => $section->schoolYear->id,
                        'year' => $section->schoolYear->school_year,
                        'semester' => $section->schoolYear->semester,
                    ] : null,
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
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }
        // Validate the request data
        $validated = $request->validate([
            'section_name' => 'required|string|max:100',
            'course_id' => 'required|exists:courses,id',
            'campus_id' => 'required|exists:campus,id',
        ]);

        // Check for duplicate section under same course
   $duplicate = sections::where('section_name', $validated['section_name'])
            ->where('course_id', $validated['course_id'])
            ->where('campus_id', $validated['campus_id'])
            ->first();

        if ($duplicate) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Section already exists under this course.',
            ], 409); //
        }

          $section = sections::create([
            'section_name' => $validated['section_name'],
            'course_id' => $validated['course_id'],
            'campus_id' => $validated['campus_id'],
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
        $section = sections::findOrFail($id);
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $validated = $request->validate([
            'section_name' => 'required|string|max:100',
            'course_id' => 'required|exists:courses,id',
            'campus_id' => 'required|exists:campus,id',
        ]);

        // Check for duplicates excluding current section
        $duplicate = sections::where('section_name', $validated['section_name'])
            ->where('course_id', $validated['course_id'])
            ->where('campus_id', $validated['campus_id'])
            ->where('id', '!=', $id)
            ->first();

        if ($duplicate) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'A section with the same name already exists under this course and campus.',
            ], 409);
        }

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

    //Dropdown for sections
    public function getSectionsDropdown()
    {
        try {

            $sections = sections::where('is_archived', 0)
                ->with(['course', 'schoolYear'])
                ->get()
                ->map(function ($section) {
                    return [
                        'id' => $section->id,
                        'section_name' => $section->section_name,
                        
                    ];
                });

            return response()->json([
                'isSuccess' => true,
                'sections' => $sections,
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to retrieve sections dropdown.',
                'error' => $e->getMessage(),
            ], 500);
        }
    } 
    
    public function getCoursesDropdown()
    {
        try {
            $courses = courses::where('is_archived', 0)
                ->select('id', 'course_name')
                ->get();

            return response()->json([
                'isSuccess' => true,
                'courses' => $courses,
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to retrieve courses dropdown.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
}
