<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\enrollments; 
use Illuminate\Validation\ValidationException;
use Throwable;      




class EnrollmentsController extends Controller
{
    public function listEnrollments()
    {
        try {
            // Retrieve all user types
            $userTypes = enrollments::where('is_archive', 0)->get();

            return response()->json([
                'isSuccess' => true,
                'userTypes' => $userTypes,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to retrieve user types.',
                'error' => $e->getMessage(),
            ], 500);
        } 
    }
}
