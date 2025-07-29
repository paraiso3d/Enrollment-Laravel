<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\admissions;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Models\accounts;
use Illuminate\Validation\Rule;
use Throwable;



class AdmissionsController extends Controller
{
  public function getAdmissions(Request $request)
{
    try {
        $query = admissions::where('status', '!=', 'archived');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('school_campus', 'like', "%$search%")
                    ->orWhere('academic_year', 'like', "%$search%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('school_campus')) {
            $query->where('school_campus', $request->school_campus);
        }

        $admissions = $query->paginate(10);

        return response()->json([
            'isSuccess' => true,
            'admissions' => $admissions->items(),
            'pagination' => [
                'current_page' => $admissions->currentPage(),
                'per_page' => $admissions->perPage(),
                'total' => $admissions->total(),
                'last_page' => $admissions->lastPage(),
            ],
        ]);
    } catch (Throwable $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Failed to retrieve admissions.',
            'error' => $e->getMessage(),
        ], 500);
    }
}





  public function applyAdmission(Request $request)
{
    try {
        $validated = $request->validate([
            'surname' => 'required|string|max:50',
            'given_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'middle_initial' => 'nullable|string|max:5',
            'user_type' => 'nullable|string',
            'suffix' => 'nullable|string|max:10',
            'date_of_birth' => 'required|date',
            'place_of_birth' => 'required|string|max:100',
            'gender' => 'required|string|max:10',
            'civil_status' => 'required|string|max:20',

            'street_address' => 'required|string|max:255',
            'province' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'barangay' => 'required|string|max:100',

            'nationality' => 'required|string|max:50',
            'religion' => 'required|string|max:50',
            'ethnic_affiliation' => 'nullable|string|max:50',
            'telephone_number' => 'nullable|string|max:15',
            'mobile_number' => 'required|string|max:15',
            'email' => 'required|email|max:100|unique:admissions,email',

            'is_4ps_member' => 'required|string',
            'is_insurance_member' => 'required|string',
            'is_vaccinated' => 'required|string',
            'is_indigenous' => 'required|string',

            'academic_program' => 'required|string|max:100',
            'school_campus' => 'required|string|max:255',
            'academic_year' => 'required|string|max:50',
            'grade_level' => 'nullable|string|max:50',
            'semester' => 'nullable|string|max:50',
            'application_type' => 'required|string|max:50',
            'classification' => 'required|string|max:50',

            'last_school_attended' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:255',

            'form_137' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'form_138' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'birth_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'good_moral' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'certificate_of_completion' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $applicantNumber = 'APLN-' . now()->format('YmdHis') . rand(100, 999);

        $admission = admissions::create([
            // You can set `account_id` to null or pass from frontend if needed
            'account_id' => null,
            'applicant_number' => $applicantNumber,
            'academic_year' => $validated['academic_year'],
            'grade_level' => $validated['grade_level'] ?? null,
            'semester' => $validated['semester'] ?? null,
            'school_campus' => $validated['school_campus'],
            'application_type' => $validated['application_type'],
            'classification' => $validated['classification'],
            'academic_program' => $validated['academic_program'],

            'first_name' => $validated['given_name'],
            'middle_name' => $validated['middle_name'] ?? '',
            'last_name' => $validated['surname'],
            'suffix' => $validated['suffix'] ?? '',
            'gender' => $validated['gender'],
            'birthdate' => $validated['date_of_birth'],
            'birthplace' => $validated['place_of_birth'],
            'civil_status' => $validated['civil_status'],
            'email' => $validated['email'],
            'contact_number' => $validated['mobile_number'],
            'street_address' => $validated['street_address'],
            'province' => $validated['province'],
            'city' => $validated['city'],
            'barangay' => $validated['barangay'],

            'nationality' => $validated['nationality'],
            'religion' => $validated['religion'],
            'ethnic_affiliation' => $validated['ethnic_affiliation'] ?? null,
            'telephone_number' => $validated['telephone_number'] ?? null,
            'is_4ps_member' => $validated['is_4ps_member'],
            'is_insurance_member' => $validated['is_insurance_member'],
            'is_vaccinated' => $validated['is_vaccinated'],
            'is_indigenous' => $validated['is_indigenous'],

            'last_school_attended' => $validated['last_school_attended'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
            'status' => 'pending',

            'form_137' => $this->moveToPublicFolder($request, 'form_137', 'form_137'),
            'form_138' => $this->moveToPublicFolder($request, 'form_138', 'form_138'),
            'birth_certificate' => $this->moveToPublicFolder($request, 'birth_certificate', 'birth_cert'),
            'good_moral' => $this->moveToPublicFolder($request, 'good_moral', 'good_moral'),
            'certificate_of_completion' => $this->moveToPublicFolder($request, 'certificate_of_completion', 'completion_cert'),
        ]);

        $course = $validated['academic_program'];
        $firstName = $validated['given_name'] ?? 'Applicant';
        $email = $validated['email'];

        Mail::html('
            <h2>Admission Application Received</h2>
            <p>Dear ' . e($firstName) . ',</p>
            <p>Thank you for applying to our institution.</p>
            <p>Your application for the <strong>' . e($course) . '</strong> program has been successfully submitted.</p>
            <p>Please wait while we review your application. Your examination form and further instructions will be sent to you shortly.</p>
            <p>Your Applicant Number is: <strong>' . e($applicantNumber) . '</strong></p>
            <p>Sincerely,<br>Admissions Office</p>
        ', function ($message) use ($email) {
            $message->to($email)
                    ->subject('Your Admission Application Has Been Received');
        });

        return response()->json([
            'isSuccess' => true,
            'message' => 'Admission application submitted successfully.',
            'admission' => $admission,
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
            'message' => 'Failed to submit admission application.',
            'error' => $e->getMessage(),
        ], 500);
    }
}



   public function approveAdmission(Request $request, $id)
{
    try {
        $approver = auth()->user();

        if (!$approver) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Unauthenticated access.',
            ], 401);
        }

        $admission = admissions::findOrFail($id);

        // Update admission status and is_admitted flag
        $admission->status = 'approved';
        $admission->status_by = $approver->id;
        $admission->is_admitted = 1;
        $admission->save();

        // Send email to applicant
        $course = $admission->academic_program ?? 'your chosen program';

        Mail::html('
            <h2>Admission Approved</h2>
            <p>Dear ' . e($admission->first_name ?? 'Applicant') . ',</p>
            <p>We are pleased to inform you that your admission to the <strong>' . e($course) . '</strong> program has been <strong>approved</strong>.</p>
            <p>Thank you for choosing our institution!</p>
        ', function ($message) use ($admission) {
            $message->to($admission->email ?? 'no-reply@example.com')
                    ->subject('Your Admission Has Been Approved');
        });

        return response()->json([
            'isSuccess' => true,
            'message' => 'Admission approved and notification email sent.',
            'admission' => $admission,
        ], 200);
    } catch (ModelNotFoundException $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Admission not found.',
        ], 404);
    } catch (Throwable $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Failed to approve admission.',
            'error' => $e->getMessage(),
        ], 500);
    }
}





    public function rejectAdmission(Request $request, $id)
    {
        try {
            $rejector = auth()->user();
            $admission = admissions::findOrFail($id);

            // Update the admission status to rejected
            $admission->status = 'rejected';
            $admission->status_by = $rejector->id;
            $admission->save();

            // Send rejection email
           Mail::html("
            <h2>Admission Rejected</h2>
            <p>Dear " . htmlspecialchars($admission->first_name ?? 'Applicant') . ",</p>
            <p>We regret to inform you that your admission application has been <strong>rejected</strong>.</p>
            <p>If you believe this was an error or would like more information, please contact our admissions office.</p>
            <br>
            <p>Thank you for your interest.</p>
        ", function ($message) use ($admission) {
            $message->to($admission->email)
                ->subject('Admission Application Status: Rejected');
        });


            return response()->json([
                'isSuccess' => true,
                'message' => 'Admission rejected and email sent successfully.',
                'admission' => $admission,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Admission not found.',
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to reject admission.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    //HELPERS
    private function moveToPublicFolder($request, $fieldName, $prefix)
    {
        if ($request->hasFile($fieldName)) {
            $file = $request->file($fieldName);
            $filename = $prefix . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Save to public folder (e.g., public/admission_files/)
            $file->move(public_path('admission_files'), $filename);

            // Return relative path for DB
            return 'admission_files/' . $filename;
        }
        return null;
    }
}
