<?php

namespace App\Http\Controllers;
use App\Models\SectionSubjectSchedule;

use Illuminate\Http\Request;

class ScheduleController extends Controller
{
public function assignSchedule(Request $request)
{
    try {
        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'day' => 'required|string|max:20',
            'time' => 'required|string|max:50',
            'room' => 'nullable|string|max:100',
            'teacher_id' => 'nullable|exists:users,id',
        ]);

        $schedule = SectionSubjectSchedule::create($validated);

        return response()->json([
            'isSuccess' => true,
            'message' => 'Schedule successfully assigned.',
            'data' => $schedule
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Failed to assign schedule.',
            'error' => $e->getMessage()
        ]);
    }
}

}
