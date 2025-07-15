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
