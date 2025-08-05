<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

use App\Models\subjects;

class SubjectsController extends Controller
{
   public function getSubjects()
{
    try {
        // Retrieve all non-archived subjects with their associated course
        $subjects = subjects::with('course')
            ->where('is_archived', 0)
            ->get()
            ->map(function ($subject) {
                return [
                    'id' => $subject->id,
                    'subject_code' => $subject->subject_code,
                    'subject_name' => $subject->subject_name,
                    'units' => $subject->units,
                    'course' => [
                        'id' => $subject->course->id,
                        'course_name' => $subject->course->course_name,
                        'course_type' => $subject->course->course_type,
                        'course_code' => $subject->course->course_code,
                        'course_description' => $subject->course->course_description,
                    ]
                ];
            });

        return response()->json([
            'isSuccess' => true,
            'subjects' => $subjects,
        ], 200);
    } catch (\Throwable $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Failed to retrieve subjects.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


   public function addSubject(Request $request)
{
    try {
        // 🔐 Check if user is authenticated and is admin
        $user = Auth::user();
    

if (!$user) {
    return response()->json([
        'isSuccess' => false,
        'message' => 'User not authenticated.',
    ], 401);
}


        // ✅ Validate input
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'subject_code' => 'required|string|max:10',
            'subject_name' => 'required|string|max:100',
            'units' => 'required|integer|min:1',

        ]);

        // 🔁 Check for duplicate subject name in the same course
        $duplicate = subjects::where('course_id', $validated['course_id'])
            ->where('subject_name', $validated['subject_name'])
            ->first();

        if ($duplicate) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Subject with the same name already exists in this course.',
            ], 409);
        }

    
        $subject = subjects::create($validated);

        return response()->json([
            'isSuccess' => true,
            'message' => 'Subject added successfully.',
            'subject' => $subject,
        ], 201);

    } catch (\Throwable $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Failed to add subject.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function updatesubject(Request $request, $id)
{
    try {
        $user = Auth::user();
        if (!$user || $user->role_name !== 'admin') {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Unauthorized access.',
            ], 403);
        }

        $subject = subjects::findOrFail($id);

        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'subject_code' => 'required|string|max:10',
            'subject_name' => 'required|string|max:100',
            'units' => 'required|integer|min:1',
        ]);

        // Check for duplicate subject name in the same course
        $duplicate = subjects::where('course_id', $validated['course_id'])
            ->where('subject_name', $validated['subject_name'])
            ->where('id', '<>', $id)
            ->first();

        if ($duplicate) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Subject with the same name already exists in this course.',
            ], 409);
        }

        $subject->update($validated);

        return response()->json([
            'isSuccess' => true,
            'message' => 'Subject updated successfully.',
            'subject' => $subject,
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
            'message' => 'Failed to update subject.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function deleteSubject($id)
{
    try {
        $user = Auth::user();
        if (!$user || $user->user_type !== 'admin') {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Unauthorized access.',
            ], 403);
        }

        $subject = subjects::findOrFail($id);
        $subject->delete();

        return response()->json([
            'isSuccess' => true,
            'message' => 'Subject deleted successfully.',
        ], 200);

    } catch (Throwable $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Failed to delete subject.',
            'error' => $e->getMessage(),
        ], 500);
    }
    }           

    

}
