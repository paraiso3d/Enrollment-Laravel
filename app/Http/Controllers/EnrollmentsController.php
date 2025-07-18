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
                'semester' => 'required|string',
                'year_level' => 'required|string',
                'enrollment_status' => 'required|string',
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
                        'semester' => $request->semester,
                        'instructor_id' => null,
                        'is_archived' => false,
                    ]);
                    $sectionId = $newSection->id;
                }
            }

            // ✅ Create the enrollment
            $enrollment = enrollments::create([
                'account_id' => $user->id,
                'admission_id' => $request->admission_id,
                'course_id' => $request->course_id,
                'section_id' => $sectionId,
                'school_year' => $schoolYear,
                'semester' => $request->semester,
                'year_level' => $request->year_level,
                'enrollment_status' => $request->enrollment_status,
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
}
