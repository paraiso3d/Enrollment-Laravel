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
use App\Models\AdmissionReservation;
use App\Models\courses;
use App\Models\school_campus;
use App\Models\school_years;
use App\Models\exam_schedules;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
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
        $query = admissions::with(['academic_program', 'schoolCampus', 'school_years'])
            ->where('status', '!=', 'archived');

        // Search by keyword
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('academic_year', 'like', "%$search%")
                    ->orWhere('first_name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%");
            });
        }

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('school_campus')) {
            $query->where('school_campus', $request->school_campus);
        }

        if ($request->has('academic_program')) {
            $query->where('academic_program', $request->academic_program);
        }

        if ($request->has('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        $admissions = $query->paginate(10);

        $admissionsData = $admissions->map(function ($admission) {
            return [
                'id' => $admission->id,
                'test_permit_no' => $admission->test_permit_no,
                'applicant_number' => $admission->applicant_number,
                'status' => $admission->status,
                'first_name' => $admission->first_name,
                'middle_name' => $admission->middle_name,
                'last_name' => $admission->last_name,
                'suffix' => $admission->suffix,
                'full_name' => trim($admission->first_name . ' ' . $admission->middle_name . ' ' . $admission->last_name . ' ' . $admission->suffix),
                'gender' => $admission->gender,
                'birthdate' => $admission->birthdate,
                'birthplace' => $admission->birthplace,
                'civil_status' => $admission->civil_status,
                'email' => $admission->email,
                'contact_number' => $admission->contact_number,
                'telephone_number' => $admission->telephone_number,
                'street_address' => $admission->street_address,
                'province' => $admission->province,
                'city' => $admission->city,
                'barangay' => $admission->barangay,
                'nationality' => $admission->nationality,
                'religion' => $admission->religion,
                'ethnic_affiliation' => $admission->ethnic_affiliation,
                'is_4ps_member' => $admission->is_4ps_member,
                'is_insurance_member' => $admission->is_insurance_member,
                'is_vaccinated' => $admission->is_vaccinated,
                'is_indigenous' => $admission->is_indigenous,
                'application_type' => $admission->application_type,
                'lrn' => $admission->lrn,
                'last_school_attended' => $admission->last_school_attended,
                'remarks' => $admission->remarks,
                'good_moral' => $admission->good_moral,
                'form_137' => $admission->form_137,
                'form_138' => $admission->form_138,
                'birth_certificate' => $admission->birth_certificate,
                'certificate_of_completion' => $admission->certificate_of_completion,
                'grade_level' => $admission->grade_level,
                'guardian_name' => $admission->guardian_name,
                'guardian_contact' => $admission->guardian_contact,
                'mother_name' => $admission->mother_name,
                'mother_contact' => $admission->mother_contact,
                'father_name' => $admission->father_name,
                'father_contact' => $admission->father_contact,
                'blood_type' => $admission->blood_type,

                // Related Names
                'academic_program' => optional($admission->academic_program)->course_name,
                'school_campus' => optional($admission->schoolCampus)->campus_name,
                'academic_year' => optional($admission->school_years)->school_year,
            ];
        });

        return response()->json([
            'isSuccess' => true,
            'admissions' => $admissionsData,
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
                'blood_type'=> 'required|string',

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

                'guardian_name' => 'nullable|string|max:100',
                'guardian_contact' => 'nullable|string|max:20',
                'mother_name' => 'nullable|string|max:100',
                'mother_contact' => 'nullable|string|max:20',
                'father_name' => 'nullable|string|max:100',
                'father_contact' => 'nullable|string|max:20',


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
                'blood_type' =>$validated['blood_type'],

                'nationality' => $validated['nationality'],
                'religion' => $validated['religion'],
                'ethnic_affiliation' => $validated['ethnic_affiliation'] ?? null,
                'telephone_number' => $validated['telephone_number'] ?? null,
                'is_4ps_member' => $validated['is_4ps_member'],
                'is_insurance_member' => $validated['is_insurance_member'],
                'is_vaccinated' => $validated['is_vaccinated'],
                'is_indigenous' => $validated['is_indigenous'],

                'guardian_name' => $validated['guardian_name'] ?? null,
                'guardian_contact' => $validated['guardian_contact'] ?? null,
                'mother_name' => $validated['mother_name'] ?? null,
                'mother_contact' => $validated['mother_contact'] ?? null,
                'father_name' => $validated['father_name'] ?? null,
                'father_contact' => $validated['father_contact'] ?? null,


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
            $lastName = $validated['surname'] ?? '';
            $email = $validated['email'];
            $appointmentDate = now()->addDays(7)->format('m/d/Y'); // Example: 7 days from now

            Mail::html("
    <div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto;'>
        <h1 style='color: #2c3e50; text-align: center;'> SNL University</h1>
        
        <h2 style='color: #2c3e50;'>Good day¹</h2>
        
        <h3 style='color: #2c3e50;'>Dear Mr./Ms. {$lastName}, {$firstName},</h3>
        
        <p>This is to inform you that we already received your online application to BuBSU - Main Campus.</p>
        <p>Please take note of your applicant number: <strong>{$applicantNumber}</strong></p>
        
        <p>Your appointment schedule for the submission of the required documents will be on <strong>{$appointmentDate}</strong></p>
        
        <p>Documents to be submitted:</p>
        <ol type='A'>
            <li>A Certified True Copy (with school sets) of School Form D (Form 13b) in Grade 11.</li>
            <li>A Certified True Copy of Proof of Residency (Barangay Certificate)</li>
            <li>A Certified True Copy of Certification / Membership Certification / Barangay-issued Certificate / ID (if applicable) of the following:
                <ol type='1'>
                    <li>Member of an Indigenous Cultural Community (GCC) / Indigenous People (SP)</li>
                    <li>Member of Paramed Family's Pilipino Program (APO)</li>
                    <li>Student with Special Needs (SSN) and other types of disabilities</li>
                    <li>Graduate of Alternative Learning System (ALS) (Accreditation dump, Equivalency Assessment and Certification)</li>
                    <li>Child of Solo Parent (Solo parent ID)</li>
                    <li>Student with Exemplary Armies and Adabute Ability (certification from the School Head)</li>
                </ol>
            </li>
        </ol>
        
        <h4 style='color: #2c3e50;'>Grounds for Disqualifications of Application:</h4>
        <ol>
            <li>Misrepresentation of the information entered in any of the submitted forms (including but not limited to the application portal)</li>
            <li>Violation of the application instructions.</li>
            <li>Non-submission of documents as scheduled.</li>
        </ol>
        
        <h4 style='color: #2c3e50;'>NOTES:</h4>
        <ul>
            <li>Transferers and Richerts who attempt to apply through Freshmen definitions will be blacklisted in all SNL programs.</li>
            <li>Students who have been admitted and enrolled in any programs will only be granted an honorable dismissal once the semester starts.</li>
        </ul>
        
        <h4 style='color: #2c3e50;'>REMINDER:</h4>
        <p>Successful Applicants must submit the complete required documents on the exact date of their Appointment. A five-to-five Administer will be administered by the SNL Administers and Orientation Services office. Kindly check your Email regularly (inbox and spam) for updates.</p>
        
        <p>To view your application details, please <a href='https://yourdomain.com/application-status'>Click Here</a></p>
        
        <p style='margin-top: 30px;'>Sincerely,<br>Admissions Office<br>SNL University</p>
    </div>
", function ($message) use ($email, $applicantNumber) {
                $message->to($email)
                    ->subject('Admission Application Received - Applicant #' . $applicantNumber);
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



    
   public function acceptapplication(Request $request, $id)
{
    try {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $admission = admissions::findOrFail($id);

        // Check if already approved
        if ($admission->status === 'approved') {
            return response()->json([
                'isSuccess' => false,
                'message' => 'This application has already been approved.',
            ]);
        }

        $admission->status = 'approved';
        $admission->save();

        $firstName = $admission->given_name ?? 'Applicant';
        $lastName = $admission->surname ?? '';
        $email = $admission->email;

        // Send confirmation email only if not previously approved
        if ($email) {
            Mail::html("
                <div style='font-family: Arial, sans-serif; max-width: 700px; margin: auto;'>
                    <h2>SNL University</h2>
                    <p>Dear Mr./Ms. {$lastName}, {$firstName},</p>

                    <p>Congratulations on completing your application.</p>

                    <p><strong>NOTE:</strong> You are requested to wait for further instructions from the BulSU Admissions and Orientation Office for your Examination schedule.</p>

                    <p>The schedule of the examination will be sent to your registered email address. Kindly check your email regularly (inbox, spam, or junk).</p>

                    <p>Follow and regularly check the SNL Admissions and Orientation Services Facebook Page for further announcements. For inquiries, you may call us at 09******* local 1087 or email us at <a href='mailto:*******@***.com'>admissions@****.****.***</a>.</p>
                </div>
            ", function ($message) use ($email) {
                $message->to($email)->subject('SNL Application Confirmation');
            });
        }

        return response()->json([
            'isSuccess' => true,
            'message' => 'Application accepted and email sent.',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Error: ' . $e->getMessage(),
        ]);
    }
}



   public function sendExamination(Request $request)
{
    try {
        $request->validate([
            'applicant_ids' => 'required|array',
            'applicant_ids.*' => 'exists:admissions,id',
            'exam_date' => 'required|date',
            'exam_time_from' => 'required|date_format:H:i',
            'exam_time_to' => 'required|date_format:H:i|after:exam_time_from',
            'room_assignment' => 'required|string',
            'building' => 'required|string',
        ]);

        $examDate = $request->input('exam_date');

        $results = [];
        foreach ($request->applicant_ids as $id) {
            try {
                $admission = admissions::with(['academic_program', 'schoolCampus', 'school_years'])->findOrFail($id);

                if (!$admission->test_permit_no) {
                    $prefix = "BULSU-";
                    $paddedId = str_pad($admission->id, 5, '0', STR_PAD_LEFT);
                    $admission->test_permit_no = $prefix . $paddedId;
                    $admission->save();
                }

                exam_schedules::updateOrCreate(
                    ['applicant_id' => $admission->id],
                    [
                        'test_permit_no' => $admission->test_permit_no,
                        'room_assignment' => $request->room_assignment,
                        'building' => $request->building,
                        'exam_time_from' => $request->exam_time_from,
                        'exam_time_to' => $request->exam_time_to,
                        'exam_date' => $examDate,
                        'testing_center' => $admission->schoolCampus->campus_name ?? 'SNL – Main Campus',
                        'academic_year' => $admission->school_years->school_year
                    ]
                );

                          $examDateFormatted = date('F d, Y', strtotime($examDate));
            $timeFormatted = date('h:i A', strtotime($request->exam_time_from)) . ' – ' . date('h:i A', strtotime($request->exam_time_to));
            $email = $admission->email;
            $firstName = $admission->first_name ?? 'Applicant';
            $lastName = $admission->last_name ?? '';
            $programName = $admission->academic_program->name ?? 'Your selected course';
            $schoolYear = $request->academic_year ?? '2024–2025';
            $testingCenter = $admission->schoolCampus->campus_name ?? 'SNL – Main Campus';

            // Send email
            if ($email) {
                Mail::html("
                    <div style='font-family: Arial, sans-serif; max-width: 700px; margin: auto;'>
                        <h2>SNL University Online Exam Schedule</h2>
                        <p>Good day!</p>
                        <p>
                            Dear Mr./Ms. {$lastName}, {$firstName},<br>
                            Course: {$programName} at SNL – {$testingCenter}
                        </p>
                        <p>Please be informed of your schedule for the Admission Test for Bulacan State University (ATSNL {$schoolYear}) on <strong>{$examDateFormatted}</strong>.</p>
                        <p>
                            <strong>Test Permit No:</strong> {$admission->test_permit_no}<br>
                            <strong>Room Assignment:</strong> {$request->room_assignment}<br>
                            <strong>Building:</strong> {$request->building}<br>
                            <strong>Time:</strong> {$timeFormatted}<br>
                            <strong>Testing Center:</strong> SNL – {$testingCenter}
                        </p>
                        <p style='font-style: italic; color: #555;'>*ATSNL will utilize all campuses of the University as Testing Centers. Your testing center assignment is computer-generated, be sure to double check your Testing Center to avoid confusion.</p>
                        <p><strong>Important Reminders:</strong></p>
                        <ul>
                            <li>PRINT your TEST PERMIT and QR Code on a short bond paper.</li>
                            <li>BRING a VALID ID (with picture) during the exam. If you do not have a valid ID, bring your PSA birth certificate and certificate of enrollment.</li>
                            <li>Give yourself extra time. Arriving early will help you locate the exam room and settle in.</li>
                            <li>Only applicants are allowed to enter. Parents/guardians/chaperones are not permitted.</li>
                            <li>Minimum health protocols will be observed. Face masks are required.</li>
                            <li>READ the General Guidelines of ATSNL {$schoolYear}. <a href='#'>Click here</a>.</li>
                            <li>To print your TEST PERMIT <a href='#'>click here</a>.</li>
                        </ul>
                        <p>*Follow and regularly check the BulSU Admissions and Orientation Services Facebook Page for announcements. For inquiries, call 919-7800 local 1087 or email <a href='mailto:admissions@bulsu.edu.ph'>admissions@bulsu.edu.ph</a>.</p>
                    </div>
                ", function ($message) use ($email) {
                    $message->to($email)->subject('SNL Exam Schedule Notification');
                });
            }

                $results[] = [
                    'applicant_id' => $id,
                    'status' => 'sent',
                ];
            } catch (\Exception $ex) {
                $results[] = [
                    'applicant_id' => $id,
                    'status' => 'error',
                    'message' => $ex->getMessage(),
                ];
            }
        }

        return response()->json([
            'isSuccess' => true,
            'message' => 'Bulk exam scheduling completed.',
            'results' => $results,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Failed to send exam schedules.',
            'error' => $e->getMessage(),
        ]);
    }
}

 public function reserveSlot(Request $request, $id)
{
    try {
        // Validate schedule date
        $request->validate([
            'schedule_date' => 'required|date|after_or_equal:today',
        ]);

        // Fetch admission with course
        $admission = admissions::with('course')->findOrFail($id);

        if (!$admission->course) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Missing course data.',
            ]);
        }

        $currentYear = Carbon::now()->year;
        $nextYear = $currentYear + 1;
        $academicYearText = $currentYear . '-' . $nextYear;

        // Format dynamic schedule range (e.g. June 1 to 15 of current year)
        $scheduleStart = Carbon::create($currentYear, 6, 1)->format('F d');
        $scheduleEnd = Carbon::create($currentYear, 6, 15)->format('d, Y');

        // Create reservation
        $reservation = new AdmissionReservation();
        $reservation->admission_id = $id;
        $reservation->schedule_date = $request->schedule_date;
        $reservation->academic_year_id = $admission->academic_year_id;
        $reservation->reservation_code = strtoupper(uniqid('RES-'));
        $reservation->save();

        // Use data directly from admissions table
        $student_name = $admission->given_name . ' ' . $admission->surname;
        $reservation_date = date('F d, Y', strtotime($request->schedule_date));

        // Email HTML content
        $htmlContent = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>SNL Online Reservation</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { color: #0056b3; border-bottom: 2px solid #0056b3; padding-bottom: 10px; }
                .footer { margin-top: 20px; font-size: 0.9em; color: #666; border-top: 1px solid #eee; padding-top: 10px; }
                .note { background-color: #f8f9fa; padding: 10px; border-left: 4px solid #6c757d; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Bulacan State University PRISMS Online Reservation</h1>
                <p>www.prismsouth.org.au/commons/cn</p>
            </div>

            <h2>Computational Quarters for AZ '. $academicYearText. ' </h2>

            <h3>Name: ' . htmlspecialchars($student_name) . '</h3>
            <p>Course: ' . htmlspecialchars($admission->course->course_name) . '</p>

            <p>In order for the Admission Office to facilitate your pre-enrollment, you must proceed to the BulSU Admission and Registration Office (Main Campus) on your <strong>specified schedule date</strong> from' . $scheduleStart . ' to ' . $scheduleEnd . '..</p>

            <p>Please bring the following:</p>
            <ol>
                <li>Printed copy of this reservation form</li>
                <li>Visit the Infirmary for Medical/Dental Examination and submit:
                    <ul>
                        <li>Completed Student Health Assessment Form</li>
                        <li>Original copy of Chest X-ray result</li>
                        <li>Medical Certificate (if applicable)</li>
                        <li>Valid ID (if applicable)</li>
                    </ul>
                </li>
            </ol>

            <div class="note">
                <p><strong>Important:</strong> Failure to visit on your specified date will result in cancellation of your reservation and you will lose the opportunity to be admitted to the University.</p>
            </div>

            <h3>RESERVATION SCHEDULE AY ' . $academicYearText . '</h3>
            <p><strong>Your reservation date:</strong> ' . htmlspecialchars($reservation_date) . '</p>
            <p>Please be guided accordingly.</p>
            <p>Thank you for choosing BulSU!</p>

            <div class="footer">
                <p><em>This is an automatically generated email - please do not reply.</em></p>
            </div>
        </body>
        </html>';

        // Send email
        Mail::send([], [], function ($message) use ($admission, $htmlContent) {
            $message->to($admission->email)
                    ->subject('SNL Online Reservation Confirmation')
                    ->setBody($htmlContent, 'text/html');
        });

        return response()->json([
            'isSuccess' => true,
            'message' => 'Reservation created and email sent successfully.',
        ]);

    } catch (ValidationException $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'isSuccess' => false,
            'message' => 'Reservation failed.',
            'error' => $e->getMessage(),
        ], 500);
    }
}



    // public function approveAdmission(Request $request, $id)
    // {
    //     try {
    //         $approver = auth()->user();

    //         if (!$approver) {
    //             return response()->json([
    //                 'isSuccess' => false,
    //                 'message' => 'Unauthenticated access.',
    //             ], 401);
    //         }

    //         $admission = admissions::findOrFail($id);

    //         $admission->status = 'approved';
    //         $admission->status_by = $approver->id;
    //         $admission->is_admitted = 1;
    //         $admission->save();

    //         // Dynamic data
    //         $firstName = $admission->first_name ?? 'Applicant';
    //         $email = $admission->email ?? 'no-reply@example.com';
    //         $course = $admission->academic_program ?? 'your chosen program';

    //         // HTML Email
    //         $html = '
    //         <div style="font-family: Arial, sans-serif; color: #333;">
    //             <div style="text-align: center; padding: 10px;">
    //                 <img src="https://yourdomain.com/logo.png" alt="Logo" style="max-height: 60px;">
    //             </div>
    //             <div style="padding: 20px;">
    //                 <h2 style="color: #2c3e50;">Congratulations, ' . e($firstName) . '!</h2>
    //                 <p>We are thrilled to inform you that your application for the <strong>' . e($course) . '</strong> program has been <span style="color: green;"><strong>approved</strong></span>.</p>
    //                 <p>We look forward to welcoming you to our institution.</p>
    //                 <p>Thank you for choosing us!</p>
    //             </div>
    //             <hr style="border: none; border-top: 1px solid #ccc;">
    //             <div style="text-align: center; font-size: 12px; color: #888; padding: 10px;">
    //                 &copy; ' . date('Y') . ' Your School Name. All rights reserved.
    //             </div>
    //         </div>
    //     ';

    //         Mail::html($html, function ($message) use ($email) {
    //             $message->to($email)
    //                 ->subject('🎉 Your Admission Has Been Approved');
    //         });

    //         return response()->json([
    //             'isSuccess' => true,
    //             'message' => 'Admission approved and notification email sent.',
    //             'admission' => $admission,
    //         ], 200);
    //     } catch (ModelNotFoundException $e) {
    //         return response()->json([
    //             'isSuccess' => false,
    //             'message' => 'Admission not found.',
    //         ], 404);
    //     } catch (Throwable $e) {
    //         return response()->json([
    //             'isSuccess' => false,
    //             'message' => 'Failed to approve admission.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }



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







    // //Email Templates
    // private function getEmailTemplate($type)
    // {
    //     $logoUrl = 'https://fileport.io/get/Cf2MRDiXkoVWEMqqlioHTQ09tN9GssRpbtZl4TCuUgneQmez_cby-fPw5cG3IqipODFod8HsL1pa3wPjOllBRufHmN8q62OOGtJH1A5jRTuXVbqlQDxjkWzC8_IWawy3O6OosYMZhtNaSesNASGE55FfUls1iLAgBiNJnZrovFOsuJRYKVqGhZ2UayJR2fuoVn9W8X0_aLwVcbf0Qo8OEuDF8r9HBOg69oGxWGk6_YWsT-0GeqHzIKzVg1Xh6EPvaR7UbkJCrXUz7u_1W5IsX9';

    //     $templates = [
    //         'entrance exam' => "
    //         <html>
    //         <head>
    //             <style>
    //                 body {
    //                     font-family: Arial, sans-serif;
    //                     background-color: #f7f9fb;
    //                     color: #333;
    //                     padding: 20px;
    //                 }
    //                 .email-container {
    //                     max-width: 600px;
    //                     margin: auto;
    //                     background: #ffffff;
    //                     padding: 30px;
    //                     border-radius: 8px;
    //                     box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    //                 }
    //                 .header {
    //                     text-align: center;
    //                     margin-bottom: 30px;
    //                 }
    //                 .footer {
    //                     margin-top: 40px;
    //                     font-size: 12px;
    //                     color: #888;
    //                     text-align: center;
    //                 }
    //             </style>
    //         </head>
    //         <body>
    //             <div class='email-container'>
    //                 <div class='header'>
    //                     <img src='{$logoUrl}' alt='Logo' height='80' />
    //                     <h2>Entrance Examination</h2>
    //                 </div>
    //                 <p>Dear <strong>{{first_name}}</strong>,</p>
    //                 <p>{{custom_message}}</p>
    //                 <p><strong>Program Applied:</strong> {{academic_program}}</p>
    //                 <p>We appreciate your interest and look forward to your success with us.</p>
    //                 <p>Best regards,<br><strong>Admissions Office</strong></p>
    //                 <div class='footer'>
    //                     &copy; " . date('Y') . " Your Institution. All rights reserved.
    //                 </div>
    //             </div>
    //         </body>
    //         </html>
    //     ",

    //         'interview_schedule' => '
    //         <html>
    //             <body>
    //                 <h2>Interview Schedule</h2>
    //                 <p>Dear {{name}},</p>
    //                 <p>You are scheduled for an interview as part of the admission process.</p>
    //                 <p><strong>Date:</strong> {{interview_date}}</p>
    //                 <p><strong>Platform:</strong> {{platform}}</p>
    //             </body>
    //         </html>
    //     ',

    //         'admission_approved' => '
    //         <html>
    //             <body>
    //                 <h2>Admission Approved</h2>
    //                 <p>Dear {{name}},</p>
    //                 <p>Congratulations! Your application for admission has been approved.</p>
    //                 <p>Please proceed with the next steps listed on our portal.</p>
    //             </body>
    //         </html>
    //     ',
    //     ];

    //     return $templates[$type] ?? null;
    // }



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
                'academic_programs' => $data
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
                'campuses' => $campuses
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
            $data = school_years::select('id', 'school_year', 'semester')->get();

            return response()->json([
                'isSuccess' => true,
                'message' => 'Academic years fetched successfully.',
                'academic_years' => $data
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
