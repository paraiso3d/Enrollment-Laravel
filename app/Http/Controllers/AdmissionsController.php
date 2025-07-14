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



    public function createAdmission(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'school_campus' => 'required|string|max:255',
                'academic_year' => 'required|string|max:255',
                'application_type' => 'required|string|max:50',
                'classification' => 'required|string|max:50',
                'grade_level' => 'required|string|max:50',
                'academic_program' => 'required|string|max:255',
                'surname' => 'required|string|max:50',
                'given_name' => 'required|string|max:50',
                'middle_name' => 'nullable|string|max:50',
                'middle_initial' => 'nullable|string|max:5',
                'suffix' => 'nullable|string|max:10',
                'date_of_birth' => 'required|date',
                'place_of_birth' => 'required|string|max:100',
                'gender' => 'required|string|max:10',
                'civil_status' => 'required|string|max:20',
                'internet_connectivity' => 'required|string|max:50',
                'learning_modality' => 'required|string|max:50',
                'digital_literacy' => 'required|string|max:50',
                'device' => 'required|string|max:50',

                //  Dropdown inputs
                'street_address' => 'required|string|max:255',
                'province' => 'required|string|max:100',
                'city' => 'required|string|max:100',
                'barangay' => 'required|string|max:100',

                'nationality' => 'required|string|max:50',
                'religion' => 'required|string|max:50',
                'ethnic_affiliation' => 'nullable|string|max:50',
                'telephone_number' => 'nullable|string|max:15',
                'mobile_number' => 'required|string|max:15',
                'email' => 'required|email|max:100',
                'is_4ps_member' => 'required|boolean',
                'is_insurance_member' => 'required|boolean',
                'vacation_status' => 'required|string|max:50',
                'is_indigenous' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validatedData = $validator->validated();
            $admission = admissions::create($validatedData); 

            return response()->json([
                'isSuccess' => true,
                'message' => 'Admission created successfully.',
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
                'message' => 'Failed to create admission.',
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
    } catch (\Throwable $e) {
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
