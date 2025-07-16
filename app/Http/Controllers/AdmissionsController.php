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
use Throwable;



class AdmissionsController extends Controller
{
   public function getAdmissions(Request $request)
{
    try {
        // Paginate admissions and load related account (10 per page)
        $admissions = admissions::with('account')->paginate(10);

        return response()->json([
            'isSuccess' => true,
            'admissions' => $admissions->items(), // admissions data on current page
            'pagination' => [
                'current_page' => $admissions->currentPage(),
                'per_page' => $admissions->perPage(),
                'total' => $admissions->total(),
                'last_page' => $admissions->lastPage(),
            ],
        ], 200);

    } catch (Throwable $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Failed to retrieve admissions.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

//TEST

   public function applyAdmission(Request $request)
{
    try {
        $account = auth()->user();

        // Validate only the fields provided by user
        $validated = $request->validate([
            
            'school_campus' => 'required|string|max:255',
            'academic_year' => 'required|string|max:50',
            'application_type' => 'required|string|max:50',
            'classification' => 'required|string|max:50',
            'grade_level' => 'required|string|max:50',
            'academic_program' => 'required|string|max:255',

            'strand' => 'nullable|string|max:50',
            'lrn' => 'nullable|string|max:20',
            'last_school_attended' => 'nullable|string|max:255',
            'school_year' => 'required|string|max:50',
            'remarks' => 'nullable|string|max:255',

            // Files
            'form_137' => 'nullable|file|mimes:pdf|max:2048',
            'form_138' => 'nullable|file|mimes:pdf|max:2048',
            'birth_certificate' => 'nullable|file|mimes:pdf|max:2048',
            'good_moral' => 'nullable|file|mimes:pdf|max:2048',
            'certificate_of_completion' => 'nullable|file|mimes:pdf|max:2048',
        ]);

        // Create a new admission record
        $admission = admissions::create([
            'account_id' => $account->id,
            'school_campus' => $validated['school_campus'],
            'academic_year' => $validated['academic_year'],
            'application_type' => $validated['application_type'],
            'classification' => $validated['classification'],
            'grade_level' => $validated['grade_level'],
            'academic_program' => $validated['academic_program'],

        // Auto-fill from account
            'first_name' => $account->given_name,
            'middle_name' => $account->middle_name,
            'last_name' => $account->surname,
            'gender' => $account->gender,
            'birthdate' => $account->date_of_birth,
            'birthplace' => $account->place_of_birth,
            'email' => $account->email,
            'contact_number' => $account->mobile_number,
            'street_address' => $account->street_address,
            'province' => $account->province,
            'city' => $account->city,
            'barangay' => $account->barangay,

            // From validated request
            'strand' => $validated['strand'],
            'lrn' => $validated['lrn'],
            'last_school_attended' => $validated['last_school_attended'],
            'school_year' => $validated['school_year'],
            'status' => 'pending',
            'remarks' => $validated['remarks'] ?? null,

            // Files (uploaded paths)
            'form_137' => $this->moveToPublicFolder($request, 'form_137', 'form_137'),
            'form_138' => $this->moveToPublicFolder($request, 'form_138', 'form_138'),
            'birth_certificate' => $this->moveToPublicFolder($request, 'birth_certificate', 'birth_cert'),
            'good_moral' => $this->moveToPublicFolder($request, 'good_moral', 'good_moral'),
            'certificate_of_completion' => $this->moveToPublicFolder($request, 'certificate_of_completion', 'completion_cert'),
        ]);

        return response()->json([
            'isSuccess' => true,
            'message' => 'Admission application submitted successfully.',
            'admission' => $admission,
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
            'message' => 'Failed to submit admission application.',
            'error' => $e->getMessage(),
        ], 500);
    }
}



    public function approveAdmission(Request $request, $id)
{
    try {
        $approver = auth()->user(); // Authenticated user
        $admission = admissions::findOrFail($id);

        // Update status
        $admission->status = 'approved';
        $admission->status_by = $approver->id;
        $admission->save();

        // Send email to applicant
        Mail::html("
            <h2>Admission Approved</h2>
            <p>Dear {$admission->first_name},</p>
            <p>We are pleased to inform you that your admission has been <strong>approved</strong>.</p>
            <p>Please expect your examination form to be sent to you shortly.</p>
            <p>Thank you for choosing our institution!</p>
        ", function ($message) use ($admission) {
            $message->to($admission->email)
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
            <p>Dear {$admission->first_name},</p>
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
