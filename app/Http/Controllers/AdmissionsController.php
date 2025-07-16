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
use Throwable;



class AdmissionsController extends Controller
{
    public function getAdmissions()
    {
        try {
            // Retrieve all admissions
            $admissions = admissions::all();

            return response()->json([
                'isSuccess' => true,
                'admissions' => $admissions,
            ], 200);
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
            

            // Validate the request data
            $validated = $request->validate([
                'school_campus' => 'required|string|max:255',
                'academic_year' => 'required|string|max:255',
                'application_type' => 'required|string|max:50',
                'classification' => 'required|string|max:50',
                'grade_level' => 'nullable|string|max:50',
                'academic_program' => 'required|string|max:255',

                // Personal Information
                'first_name' => 'required|string|max:50',
                'middle_name' => 'nullable|string|max:50',
                'last_name' => 'required|string|max:50',
                'gender' =>   'required|string|',
                'birthdate' => 'required|date',
                'birthplace' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'contact_number' => 'required|string|max:20',
                'street_address' => 'required|string|max:255',
                'province' => 'required|string|max:100',
                'city' => 'required|string|max:100',
                'barangay' => 'required|string|max:100',

                // Academic background 
                'strand' => 'nullable|string|max:50',
                'lrn' => 'nullable|string|max:20',
                'last_school_attended' => 'nullable|string|max:255',

                // System/processing fields
                'school_year' => 'required|string|max:50',
                'status' => 'nullable|string|max:50',
                'remarks' => 'nullable|string|max:255',


                // Files
                'form_137_path' => 'nullable|file|mimes:pdf|max:2048',
                'form_138_path' => 'nullable|file|mimes:pdf|max:2048',
                'birth_certificate_path' => 'nullable|file|mimes:pdf|max:2048',
                'good_moral_path' => 'nullable|file|mimes:pdf|max:2048',
                'certificate_of_completion_path' => 'nullable|file|mimes:pdf|max:2048',
            ]);

            $account = auth()->user();

            // Create a new admission record
            $admission = admissions::create([
                'account_id' => $account->id,
                'school_campus' => $validated['school_campus'],
                'academic_year' => $validated['academic_year'],
                'application_type' => $validated['application_type'],
                'classification' => $validated['classification'],
                'grade_level' => $validated['grade_level'],
                'academic_program' => $validated['academic_program'],


                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'],
                'last_name' => $validated['last_name'],
                'gender' => $validated['gender'],
                'birthdate' => $validated['birthdate'],
                'birthplace' => $validated['birthplace'],
                'email' => $validated['email'],
                'contact_number' => $validated['contact_number'],
                'street_address' => $validated['street_address'],
                'province' => $validated['province'],
                'city' => $validated['city'],
                'barangay' => $validated['barangay'],

                // Academic background
                'strand' => $validated['strand'],
                'lrn' => $validated['lrn'],
                'last_school_attended' => $validated['last_school_attended'],


                // System processing fields
                'school_year' => $validated['school_year'],
                'status' => 'pending', 
                'remarks' => $validated['remarks'] ?? null,

                // Files
                'form_137_path' => $request->file('form_137_path') ? $request->file('form_137_path')->store('uploads', 'public') : null,
                'form_138_path' => $request->file('form_138_path') ? $request->file('form_138_path')->store('uploads', 'public') : null,
                'birth_certificate_path' => $request->file('birth_certificate_path') ? $request->file('birth_certificate_path')->store('uploads', 'public') : null,
                'good_moral_path' => $request->file('good_moral_path') ? $request->file('good_moral_path')->store('uploads', 'public') : null,
                'certificate_of_completion_path' => $request->file('certificate_of_completion_path') ? $request->file('certificate_of_completion_path')->store('uploads', 'public') : null,
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
            $admission = admissions::findOrFail($id);

            // Update status
            $admission->status = 'approved';
            $admission->save();

            // Generate random password
            $password = Str::random(10);

            // Create account
            $account = new admissions();
            $account->email = $admission->email;
            $account->password = bcrypt($password);
            $account->user_type_id = 3; // adjust if needed
            $account->save();

            // Send email (inline HTML)
            Mail::html("
            <h2>Admission Approved</h2>
            <p>Dear Applicant,</p>
            <p>Your admission has been <strong>approved</strong>. Below are your login credentials:</p>
            <p><strong>Email:</strong> {$admission->email}</p>
            <p><strong>Password:</strong> {$password}</p>
            <p>You can now access your account. Please change your password after logging in.</p>
            <br>
            <p>Thank you!</p>
        ", function ($message) use ($admission) {
                $message->to($admission->email)
                    ->subject('Your Admission Has Been Approved');
            });

            return response()->json([
                'isSuccess' => true,
                'message' => 'Admission approved and email sent.',
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
            // Find the admission by ID
            $admission = admissions::findOrFail($id);

            // Update the admission status to rejected
            $admission->status = 'rejected'; // assuming you have a status field
            $admission->save();

            return response()->json([
                'isSuccess' => true,
                'message' => 'Admission rejected successfully.',
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
}
