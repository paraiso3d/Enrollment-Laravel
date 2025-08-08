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
        // Retrieve all non-archived subjects along with their course
        $subjects = subjects::with('course')
            ->where('is_archived', 0)
            ->get()
            ->map(function ($subject) {
                return [
                    'id' => $subject->id,
                    'subject_code' => $subject->subject_code,
                    'subject_name' => $subject->subject_name,
                    'units' => $subject->units,
                    'course_id' => $subject->course_id,
                    'course_name' => $subject->course ? $subject->course->course_name : null,
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



   public function addCurriculum(Request $request)
{
    try {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'curriculum_name' => 'required|string|max:255',
            'year_started' => 'required|integer',
        ]);

        $curriculum = curriculum::create([
            'course_id' => $request->course_id,
            'curriculum_name' => $request->curriculum_name,
            'year_started' => $request->year_started,
        ]);

        return response()->json([
            'isSuccess' => true,
            'message' => 'Curriculum added successfully.',
            'curriculum' => [
                'id' => $curriculum->id,
                'course_id' => $curriculum->course_id,
                'curriculum_name' => $curriculum->curriculum_name,
                'year_started' => $curriculum->year_started,
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Failed to add curriculum.',
            'error' => $e->getMessage()
        ]);
    }
}

   public function updateCurriculum(Request $request, $id)
{
    try {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'curriculum_name' => 'required|string|max:255',
            'year_started' => 'required|integer',
        ]);

        $curriculum = curriculum::find($id);

        if (!$curriculum) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Curriculum not found.'
            ]);
        }

        $curriculum->update([
            'course_id' => $request->course_id,
            'curriculum_name' => $request->curriculum_name,
            'year_started' => $request->year_started,
        ]);

        return response()->json([
            'isSuccess' => true,
            'message' => 'Curriculum updated successfully.',
            'curriculum' => [
                'id' => $curriculum->id,
                'course_id' => $curriculum->course_id,
                'curriculum_name' => $curriculum->curriculum_name,
                'year_started' => $curriculum->year_started,
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Failed to update curriculum.',
            'error' => $e->getMessage()
        ]);
    }
}



    public function deleteSubject($id)
    {
        try {
            $user = auth()->user();
            $user->load('userType'); // Eager load the relationship

            if (!$user || $user->userType?->role_name !== 'admin') {
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

    //Dropdown

    public function getSubjectsDropdown()
{
    try {
        $subjects = subjects::where('is_archived', 0)
            ->pluck('subject_name', 'id')
            ->map(function ($subject_name, $id) {
                return [
                    'id' => $id,
                    'subject_name' => $subject_name,
                ];
            })
            ->values(); // Reset array keys for clean indexing

        return response()->json([
            'isSuccess' => true,
            'subject' => $subjects,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Failed to retrieve subjects.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}
