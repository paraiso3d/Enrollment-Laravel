<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\enrollments;
use Illuminate\Support\Facades\Validator;
use Illuminate\validation\Rule;
use App\Models\admissions;
use App\Models\sections;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Models\accounts;
use App\Models\students;
use App\Models\subjects;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Throwable;


class EnrollmentsController extends Controller
{
    public function listEnrollments()
    {
        try {
            // Fetch enrollments where is_archive is 0
            $enrollments = enrollments::where('is_archive', 0)->get();

            return response()->json([
                'isSuccess' => true,
                'enrollments' => $enrollments,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to retrieve enrollments.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getApprovedAdmissions()
    {
        try {
            $admissions = admissions::where('status', 'approved')->get();
            return response()->json([
                'isSuccess' => true,
                'data' => $admissions
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to fetch approved admissions.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function enrollStudent(Request $request)
{
    try {
        $validated = $request->validate([
            'admission_id' => 'required|integer|exists:admissions,id',
        ]);

        $admission = admissions::find($validated['admission_id']);

        // ✅ Check if already enrolled
        $existingStudent = students::where('admission_id', $admission->id)->first();
        if ($existingStudent) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'This admission is already enrolled.',
            ], 400);
        }

        // ✅ Generate student number like: SNL-202508061001
        $date = now()->format('Ymd');
        $lastStudent = students::latest('id')->first();
        $nextId = $lastStudent ? $lastStudent->id + 1 : 1;
        $studentNumber = 'SNL-' . $date . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        // ✅ Generate random 8-character password
        $rawPassword = Str::random(8);
        $hashedPassword = Hash::make($rawPassword);

        // ✅ Create student record
        $student = new students();
        $student->admission_id = $admission->id;
        $student->student_number = $studentNumber;
        $student->password = $hashedPassword;
        $student->profile_img = null;
        $student->student_status = 0;
        $student->section_id = null; // Set your default section
        $student->is_active = 1;
        $student->save();

        // ✅ Send email using HTML (no Blade)
        $html = '
            <html>
            <body style="font-family: Arial, sans-serif;">
                <div style="border:1px solid #ccc; padding:20px; max-width:600px; margin:auto;">
                    <h2>Enrollment Confirmation</h2>
                    <p>Hello <strong>' . $admission->given_name . ' ' . $admission->surname . '</strong>,</p>
                    <p>You have been successfully enrolled. Here are your login credentials:</p>
                    <ul>
                        <li><strong>Student Number:</strong> ' . $studentNumber . '</li>
                        <li><strong>Password:</strong> ' . $rawPassword . '</li>
                    </ul>
                    <p>Please keep this information secure.</p>
                    <br>
                    <p>Best regards,<br>Enrollment Team</p>
                </div>
            </body>
            </html>
        ';

        Mail::send([], [], function ($message) use ($admission, $html) {
            $message->to($admission->email)
                ->subject('Your Enrollment Credentials')
                ->setBody($html, 'text/html');
        });

        return response()->json([
            'isSuccess' => true,
            'message' => 'Student enrolled successfully and credentials sent to email.',
            'student_number' => $studentNumber,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Error: ' . $e->getMessage(),
        ], 500);
    }
}


public function enrollNow(Request $request)
{
    try {
        $student = auth()->user();  // already authenticated student

        if (!$student) {
            Log::warning('Unauthenticated access attempt.');
            return response()->json([
                'isSuccess' => false,
                'message' => 'Unauthenticated user.'
            ], 401);
        }

        Log::info('Authenticated student:', ['student_id' => $student->id]);

        // Eager load the admission relationship
        $student->load('admission');

        if (!$student->admission) {
            Log::error('Admission not found for student ID: ' . $student->id);
            return response()->json([
                'isSuccess' => false,
                'message' => 'Student admission not found.'
            ], 404);
        }

        $validated = $request->validate([
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'exists:subjects,id',
        ]);

        // Use academic_program_id instead of course_id
        $courseId = $student->admission->academic_program_id ?? null;
        $schoolYearId = $student->admission->school_year_id ?? null;

        Log::info('Course and School Year from admission:', [
            'academic_program_id' => $courseId,
            'school_year_id' => $schoolYearId
        ]);

        if (!$courseId) {
            Log::error('Missing academic_program_id in student admission.');
            return response()->json([
                'isSuccess' => false,
                'message' => 'Course not found in admission.'
            ], 404);
        }

        $section = sections::where('course_id', $courseId)->first();
        Log::info('Section fetched:', ['section' => $section]);

        if (!$section) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'No available section found for the student’s course.'
            ], 404);
        }

        $student->section_id = $section->id;
        $student->student_status = 1;
        $student->save();

        $enrolledSubjectIds = collect();

        // Fetch subjects for course and school year
        $autoSubjects = subjects::where('course_id', $courseId)
            ->when($schoolYearId, function ($query) use ($schoolYearId) {
                return $query->where('school_year_id', $schoolYearId);
            })
            ->pluck('id');

        Log::info('Auto subjects fetched:', ['auto_subject_ids' => $autoSubjects]);

        if ($autoSubjects->isNotEmpty()) {
            $enrolledSubjectIds = $autoSubjects;
        } elseif (!empty($validated['subject_ids'])) {
            $enrolledSubjectIds = collect($validated['subject_ids']);
        } else {
            return response()->json([
                'isSuccess' => false,
                'message' => 'No subjects found for course and year, and no manual subjects provided.'
            ], 404);
        }

        $student->subjects()->sync($enrolledSubjectIds);

        return response()->json([
            'isSuccess' => true,
            'message' => 'Student enrolled and subjects assigned successfully.',
            'enrolled' => [
                'student_number' => $student->student_number,
                'section' => $section->section_name ?? null,
                'subjects' => subjects::whereIn('id', $enrolledSubjectIds)->get([
                    'id', 'subject_code', 'subject_name'
                ]),
            ]
        ]);
    } catch (\Exception $e) {
        Log::error('Enrollment error:', ['exception' => $e->getMessage()]);
        return response()->json([
            'isSuccess' => false,
            'message' => 'Enrollment failed.',
            'error' => $e->getMessage()
        ], 500);
    }
}












    //This function handles the creation of a new enrollment.
    public function storeEnrollment(Request $request)
    {
        
        try {
            $user = auth()->user();

            $last = enrollments::orderBy('id', 'desc')->first();
            $nextId = $last ? $last->id + 1 : 1;
            $studentNumber = 'SN-' . now()->format('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            $validator = Validator::make($request->all(), [
                'admission_id' => [
                    'nullable',
                    Rule::exists('admissions', 'id')->where(function ($query) {
                        $query->where('status', 'approved');
                    }),
                ],
                'course_id' => 'required|exists:courses,id',
                'section_id' => 'nullable|exists:sections,id',
                'is_enrolled' => 'required|boolean',
                'is_irregular' => 'boolean',
                'date_enrolled' => 'required|date',
                'remarks' => 'nullable|string',
                'is_archived' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->admission_id) {
                $admission = admissions::findOrFail($request->admission_id);
                $schoolYear = $admission->school_year;
            } else {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Admission ID is required if no school year is provided.',
                ], 422);
            }

            // ✅ Auto sectioning logic if section_id is not provided
            $sectionId = $request->section_id;
            if (!$sectionId) {
                $existingSection = sections::where('course_id', $request->course_id)
                    ->where('semester', $request->semester)
                    ->where('school_year_id', $admission->school_year_id ?? 1) // fallback if not available
                    ->get();

                $assigned = false;
                foreach ($existingSection as $section) {
                    $enrolledCount = enrollments::where('section_id', $section->id)
                        ->where('school_year', $admission->school_year)
                        ->where('semester', $request->semester)
                        ->count();

                    if ($enrolledCount < 35) {
                        $sectionId = $section->id;
                        $assigned = true;
                        break;
                    }
                }


                if (!$assigned) {
                    $sectionName = 'Section-' . strtoupper(substr(uniqid(), -4));
                    $newSection = sections::create([
                        'section_name' => $sectionName,
                        'course_id' => $request->course_id,
                        'school_year_id' => $admission->school_year_id ?? 1,
                        'is_archived' => false,
                    ]);
                    $sectionId = $newSection->id;
                }
            }

            // ✅ Create the enrollment
            $enrollment = enrollments::create([
                'admission_id' => $request->admission_id,
                'course_id' => $request->course_id,
                'section_id' => $sectionId,
                'school_year' => $schoolYear,
                'is_enrolled' => 1,
                'is_irregular' => $request->is_irregular ?? false,
                'date_enrolled' => $request->date_enrolled,
                'remarks' => $request->remarks,
                'student_number' => $studentNumber,
                'is_archived' => $request->is_archived ?? false,
            ]);

            return response()->json([
                'isSuccess' => true,
                'message' => 'Enrollment created successfully',
                'data' => $enrollment
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Enrollment creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //HELPERS
    public function getAvailableSubjects()
{
    $student = auth()->user()->student;

    if (!$student || !$student->admission) {
        return response()->json(['message' => 'Student not found or not admitted.'], 404);
    }

    $courseId = $student->admission->course_id;
    $schoolYearId = $student->admission->school_year_id;
    $semester = $student->admission->semester;

    $subjects = subjects::where('course_id', $courseId)
        ->where('school_year_id', $schoolYearId)
        ->where('semester', $semester)
        ->get();

    return response()->json([
        'isSuccess' => true,
        'data' => $subjects
    ]);
}

}
