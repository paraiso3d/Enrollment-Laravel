<?php

namespace App\Http\Controllers;

use App\Models\curriculums;
use Illuminate\Http\Request;

class CurriculumController extends Controller
{

    public function getCurriculums()
{
    $curriculums = curriculums::with(['subjects', 'course'])->get();

    return response()->json([
        'isSuccess' => true,
        'message' => 'Curriculums retrieved successfully.',
        'curriculum' => $curriculums,
    ]);
}

    // Store a new curriculum
   public function storecurriculum(Request $request)
{
    $validated = $request->validate([
        'curriculum_name' => 'required|string|max:255',
        'curriculum_description' => 'nullable|string',
        'course_id' => 'required|exists:courses,id',
        'subject_ids' => 'nullable|array',
        'subject_ids.*' => 'exists:subjects,id',
    ]);

    $curriculum = curriculums::create($validated);

    if (!empty($validated['subject_ids'])) {
        $curriculum->subjects()->attach($validated['subject_ids']);
    }

    return response()->json([
        'isSuccess' => true,
        'message' => 'Curriculum created successfully.',
        'curriculum' => $curriculum->load('subjects'),
    ]);
}




    // Show a specific curriculum with subjects
    public function showcurriculum($id)
    {
        $curriculum = curriculums::with(['course', 'subjects'])->findOrFail($id);

        return response()->json([
            'isSuccess' => true,
            'curriculum' => $curriculum,
        ]);
    }

    // Update a curriculum
    public function updatecurriculum(Request $request, $id)
{
    $curriculum = curriculums::findOrFail($id);

    $validated = $request->validate([
        'curriculum_name' => 'sometimes|required|string|max:255',
        'curriculum_description' => 'nullable|string',
        'course_id' => 'sometimes|required|exists:courses,id',
        'subject_ids' => 'nullable|array', // Add this
        'subject_ids.*' => 'exists:subjects,id', // Validate each subject ID
    ]);

    // Update curriculum fields
    $curriculum->update($validated);

    // Sync subjects if provided
    if (isset($validated['subject_ids'])) {
        $curriculum->subjects()->sync($validated['subject_ids']);
    }

    return response()->json([
        'isSuccess' => true,
        'message' => 'Curriculum updated successfully.',
        'curriculum' => $curriculum->load('subjects'), // include related subjects in response
    ]);
}

    // Delete (archive) a curriculum
    public function destroycurriculum($id)
    {
        $curriculum = curriculums::findOrFail($id);
        $curriculum->delete();

        return response()->json([
            'isSuccess' => true,
            'message' => 'Curriculum deleted successfully.',
        ]);
    }
}

