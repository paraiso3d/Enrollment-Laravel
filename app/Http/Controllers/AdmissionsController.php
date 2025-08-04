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
use App\Models\courses;
use App\Models\school_campus;
use App\Models\school_years;
use Illuminate\Validation\Rule;
use Throwable;
use Exception;
use Illuminate\Support\Facades\Log;



class AdmissionsController extends Controller
{



    public function getAdmissionById($id)
    {
        try {
            $admission = admissions::where('id', $id)
                ->where('is_archived', 0)
                ->first();

            if (!$admission) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Admission not found.',
                ], 404);
            }

            return response()->json([
                'isSuccess' => true,
                'message' => 'Admission retrieved successfully.',
                'admission' => $admission,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to retrieve admission.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function getAdmissions(Request $request)
    {
        try {
            $query = admissions::where('status', '!=', 'archived');

            // Search by keyword
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->Where('academic_year', 'like', "%$search%")
                        ->orWhere('first_name', 'like', "%$search%")
                        ->orWhere('last_name', 'like', "%$search%");
                });
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by school campus
            if ($request->has('school_campus')) {
                $query->where('school_campus', $request->school_campus);
            }

            // Filter by academic program
            if ($request->has('academic_program')) {
                $query->where('academic_program', $request->academic_program);
            }
            
            // Filter by academic year
            if ($request->has('academic_year')) {
                $query->where('academic_year', $request->academic_year);
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



   public function sendManualAdmissionEmail(Request $request)
{
    $request->validate([
        'emails' => 'required|array',
        'emails.*' => 'email',
        'subject' => 'required|string',
        'custom_message' => 'required|string',
    ]);

    $failedEmails = [];

    foreach ($request->emails as $email) {
        $admission = admissions::where('email', $email)->first();

        if (!$admission) {
            $failedEmails[] = $email;
            continue;
        }

        $logoUrl = 'https://fileport.io/get/Cf2MRDiXkoVWEMqqlioHTQ09tN9GssRpbtZl4TCuUgneQmez_cby-fPw5cG3IqipODFod8HsL1pa3wPjOllBRufHmN8q62OOGtJH1A5jRTuXVbqlQDxjkWzC8_IWawy3O6OosYMZhtNaSesNASGE55FfUls1iLAgBiNJnZrovFOsuJRYKVqGhZ2UayJR2fuoVn9W8X0_aLwVcbf0Qo8OEuDF8r9HBOg69oGxWGk6_YWsT-0GeqHzIKzVg1Xh6EPvaR7UbkJCrXUz7u_1W5IsX9'; // Replace with your actual logo URL

        $htmlContent = "
        <html>
        <head>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f7f9fb;
                    color: #333;
                    padding: 20px;
                }
                .email-container {
                    max-width: 600px;
                    margin: auto;
                    background: #ffffff;
                    padding: 30px;
                    border-radius: 8px;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                }
                .footer {
                    margin-top: 40px;
                    font-size: 12px;
                    color: #888;
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='header'>
                    <img src='{$logoUrl}' alt='Logo' height='80' />
                    <h2>Entrance Examination</h2>
                </div>
                <p>Dear <strong>{$admission->first_name}</strong>,</p>
                <p>{$request->custom_message}</p>
                <p><strong>Program Applied:</strong> {$admission->academic_program}</p>
                <p>We appreciate your interest and look forward to your success with us.</p>
                <p>Best regards,<br><strong>Admissions Office</strong></p>

                <div class='footer'>
                    &copy; " . date('Y') . " Your Institution. All rights reserved.
                </div>
            </div>
        </body>
        </html>
        ";

        try {
            Mail::send([], [], function ($message) use ($email, $htmlContent, $request) {
                $message->to($email)
                    ->subject($request->subject)
                    ->setBody($htmlContent, 'text/html');
            });
        } catch (\Exception $e) {
            $failedEmails[] = $email;
        }
    }

    return response()->json([
        'isSuccess' => count($failedEmails) === 0,
        'message' => count($failedEmails) === 0
            ? 'Emails sent successfully.'
            : 'Some emails failed to send.',
        'failed' => $failedEmails
    ]);
}


public function sendCustomEmail(Request $request)
{
    $validated = $request->validate([
        'recipient_email' => 'required|email',
        'recipient_name' => 'required|string|max:255',
        'email_type' => 'required|string', // e.g., entrance_exam, interview_schedule, etc.
        'custom_data' => 'nullable|array'  // contains placeholders like exam_date, location, etc.
    ]);

    $template = $this->getEmailTemplate($validated['email_type']);

    if (!$template) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Invalid email template selected.',
        ], 400);
    }

    // Replace placeholders
    $placeholders = $validated['custom_data'] ?? [];
    $placeholders['name'] = $validated['recipient_name'];
    foreach ($placeholders as $key => $value) {
        $template = str_replace('{{' . $key . '}}', $value, $template);
    }

    // Send the email using Mail::html instead of Mail::raw
    Mail::html($template, function ($message) use ($validated) {
        $message->to($validated['recipient_email'], $validated['recipient_name'])
                ->subject(Str::title(str_replace('_', ' ', $validated['email_type'])));
    });

    return response()->json([
        'isSuccess' => true,
        'message' => 'Email sent successfully.',
    ]);
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

            'academic_program_id' => 'required|exists:courses,id',
            'school_campus_id' => 'required|exists:school_campus,id',
            'academic_year_id' => 'required|exists:school_years,id',
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

        // Fetch names from related tables using IDs
        $campusName = school_campus::find($validated['school_campus_id'])->campus_name;
        $academicYear = school_years::find($validated['academic_year_id'])->year;
        $programName = courses::find($validated['academic_program_id'])->course_name;


        $applicantNumber = 'APLN-' . now()->format('YmdHis') . rand(100, 999);

        $admission = admissions::create([
            'account_id' => null,
            'applicant_number' => $applicantNumber,
            'academic_year_id' => $validated['academic_year_id'],
            'grade_level' => $validated['grade_level'] ?? null,
            'semester' => $validated['semester'] ?? null,
            'school_campus_id' => $validated['school_campus_id'],
            'application_type' => $validated['application_type'],
            'classification' => $validated['classification'],
            'academic_program_id' => $validated['academic_program_id'],

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

        // Send email
        $firstName = $validated['given_name'] ?? 'Applicant';
        $email = $validated['email'];

        Mail::html("
            <h2>Admission Application Received</h2>
            <p>Dear {$firstName},</p>
            <p>Thank you for applying to our institution.</p>
            <p>Your application for the <strong>{$programName}</strong> program has been successfully submitted.</p>
            <p>Please wait while we review your application. Your examination form and further instructions will be sent to you shortly.</p>
            <p>Your Applicant Number is: <strong>{$applicantNumber}</strong></p>
            <p>Sincerely,<br>Admissions Office</p>
        ", function ($message) use ($email) {
            $message->to($email)
                ->subject('Your Admission Application Has Been Received');
        });

        return response()->json([
            'isSuccess' => true,
            'message' => 'Admission application submitted successfully.',
            'admission' => $admission,
            'academic_program' => $programName,
            'school_campus' => $campusName,
            'academic_year' => $academicYear,
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

            $admission->status = 'approved';
            $admission->status_by = $approver->id;
            $admission->is_admitted = 1;
            $admission->save();

            // Dynamic data
            $firstName = $admission->first_name ?? 'Applicant';
            $email = $admission->email ?? 'no-reply@example.com';
            $course = $admission->academic_program ?? 'your chosen program';

            // HTML Email
            $html = '
            <div style="font-family: Arial, sans-serif; color: #333;">
                <div style="text-align: center; padding: 10px;">
                    <img src="https://yourdomain.com/logo.png" alt="Logo" style="max-height: 60px;">
                </div>
                <div style="padding: 20px;">
                    <h2 style="color: #2c3e50;">Congratulations, ' . e($firstName) . '!</h2>
                    <p>We are thrilled to inform you that your application for the <strong>' . e($course) . '</strong> program has been <span style="color: green;"><strong>approved</strong></span>.</p>
                    <p>We look forward to welcoming you to our institution.</p>
                    <p>Thank you for choosing us!</p>
                </div>
                <hr style="border: none; border-top: 1px solid #ccc;">
                <div style="text-align: center; font-size: 12px; color: #888; padding: 10px;">
                    &copy; ' . date('Y') . ' Your School Name. All rights reserved.
                </div>
            </div>
        ';

            Mail::html($html, function ($message) use ($email) {
                $message->to($email)
                    ->subject('🎉 Your Admission Has Been Approved');
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

            // Update admission status
            $admission->status = 'rejected';
            $admission->status_by = $rejector->id;
            $admission->save();

            // HTML Email content
            $htmlContent = "
            <div style='font-family: Arial, sans-serif; padding: 20px; max-width: 600px; margin: auto; border: 1px solid #ddd;'>
                <div style='text-align: center; margin-bottom: 20px;'>
                    <img src='" . asset('storage/logo.png') . "' alt='Institution Logo' style='max-height: 80px;'>
                </div>

                <h2 style='color: #e53e3e;'>Admission Rejected</h2>
                <p>Dear <strong>" . e($admission->first_name ?? 'Applicant') . "</strong>,</p>
                <p>We regret to inform you that your admission application has been <strong>rejected</strong>.</p>
                <p>If you believe this was an error or need further assistance, please contact our admissions office.</p>

                <br>
                <p style='color: #718096;'>Thank you for your interest in our institution.</p>

                <hr style='margin: 30px 0;'>
                <footer style='text-align: center; font-size: 12px; color: #a0aec0;'>
                    &copy; " . date('Y') . " Your Institution Name. All rights reserved.
                </footer>
            </div>
        ";

            // Send rejection email
            Mail::html($htmlContent, function ($message) use ($admission) {
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







    //Email Templates
   private function getEmailTemplate($type)
{
    $logoUrl = 'https://fileport.io/get/Cf2MRDiXkoVWEMqqlioHTQ09tN9GssRpbtZl4TCuUgneQmez_cby-fPw5cG3IqipODFod8HsL1pa3wPjOllBRufHmN8q62OOGtJH1A5jRTuXVbqlQDxjkWzC8_IWawy3O6OosYMZhtNaSesNASGE55FfUls1iLAgBiNJnZrovFOsuJRYKVqGhZ2UayJR2fuoVn9W8X0_aLwVcbf0Qo8OEuDF8r9HBOg69oGxWGk6_YWsT-0GeqHzIKzVg1Xh6EPvaR7UbkJCrXUz7u_1W5IsX9';

    $templates = [
        'entrance exam' => "
            <html>
            <head>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        background-color: #f7f9fb;
                        color: #333;
                        padding: 20px;
                    }
                    .email-container {
                        max-width: 600px;
                        margin: auto;
                        background: #ffffff;
                        padding: 30px;
                        border-radius: 8px;
                        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 30px;
                    }
                    .footer {
                        margin-top: 40px;
                        font-size: 12px;
                        color: #888;
                        text-align: center;
                    }
                </style>
            </head>
            <body>
                <div class='email-container'>
                    <div class='header'>
                        <img src='{$logoUrl}' alt='Logo' height='80' />
                        <h2>Entrance Examination</h2>
                    </div>
                    <p>Dear <strong>{{first_name}}</strong>,</p>
                    <p>{{custom_message}}</p>
                    <p><strong>Program Applied:</strong> {{academic_program}}</p>
                    <p>We appreciate your interest and look forward to your success with us.</p>
                    <p>Best regards,<br><strong>Admissions Office</strong></p>
                    <div class='footer'>
                        &copy; " . date('Y') . " Your Institution. All rights reserved.
                    </div>
                </div>
            </body>
            </html>
        ",

        'interview_schedule' => '
            <html>
                <body>
                    <h2>Interview Schedule</h2>
                    <p>Dear {{name}},</p>
                    <p>You are scheduled for an interview as part of the admission process.</p>
                    <p><strong>Date:</strong> {{interview_date}}</p>
                    <p><strong>Platform:</strong> {{platform}}</p>
                </body>
            </html>
        ',

        'admission_approved' => '
            <html>
                <body>
                    <h2>Admission Approved</h2>
                    <p>Dear {{name}},</p>
                    <p>Congratulations! Your application for admission has been approved.</p>
                    <p>Please proceed with the next steps listed on our portal.</p>
                </body>
            </html>
        ',
    ];

    return $templates[$type] ?? null;
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


    //Dropdowns
    public function getAdmissionStatuses()
    {
        $statuses = admissions::where('is_archived', 0)
            ->select('id', 'status')
            ->get()
            ->groupBy('status')
            ->map(function ($items, $status) {
                $first = $items->first();
                return [
                    'id' => $first->id,
                    'status' => ucfirst($status),
                ];
            })
            ->values();

        return response()->json([
            'isSuccess' => true,
            'message' => 'Admission statuses retrieved successfully.',
            'statuses' => $statuses
        ]);
    }

    public function getAcademicProgramsDropdown()
{
    try {
        $data = courses::select('id', 'course_name')->get();

        return response()->json([
            'isSuccess' => true,
            'message' => 'Academic programs fetched successfully.',
            'data' => $data
        ]);
    } catch (Exception $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Failed to fetch academic programs.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


   public function getSchoolCampusesDropdown()
{
    try {
        $campuses = school_campus::select('id', 'campus_name')->get();

        return response()->json([
            'isSuccess' => true,
            'message' => 'School campuses fetched successfully.',
            'data' => $campuses
        ]);
    } catch (Exception $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Failed to fetch school campuses.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function getAcademicYearsDropdown()
{
    try {
        $data = school_years::select('id', 'school_year')->get();

        return response()->json([
            'isSuccess' => true,
            'message' => 'Academic years fetched successfully.',
            'data' => $data
        ]);
    } catch (Exception $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Failed to fetch academic years.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


}
