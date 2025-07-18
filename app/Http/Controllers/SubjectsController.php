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
            // Retrieve all subjects
            $subjects = subjects::all();

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
        if (!$user || $user->user_type !== 'admin') {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Unauthorized access.',
            ], 403);
        }

        // ✅ Validate input
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'subject_code' => 'required|string|max:10',
            'subject_name' => 'required|string|max:100',
            'units' => 'required|integer|min:1',
            'semester' => 'required|string|max:20',
            'year_level' => 'required|integer|min:1|max:4',
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

    

}
