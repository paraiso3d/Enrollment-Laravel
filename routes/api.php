<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoursesController;
use App\Http\Controllers\EnrollmentsController;
use App\Http\Controllers\SchoolYearsController;
use App\Http\Controllers\SectionsController;
use App\Http\Controllers\UserTypesController;
use App\Http\Controllers\SchoolCampusController;
use App\Http\Controllers\AdmissionsController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\SubjectsController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
// Done Integration
Route::get('/login/google', [SocialAuthController::class, 'redirectToGoogle']);
Route::get('/login/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

Route::get('/auth/github/redirect', [SocialAuthController::class, 'redirectToGithub']);
Route::get('/auth/github/callback', [SocialAuthController::class, 'handleGithubCallback']);
// Admissions

Route::post('createuser', [AccountsController::class, 'createUser']);
Route::post('createadmin', [AccountsController::class, 'createAdminAccount']);
Route::post('createinstructor',[AccountsController::class, 'createInstructor']);
Route::get('getusertypes', [UserTypesController::class, 'getUserTypes']);

// Login and Logout 
Route::post('login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
});

//Courses Management
Route::middleware('auth:sanctum')->group(function () {
    Route::get('getcourses', [CoursesController::class, 'getCourses']);
    Route::get('/courses/{id}/subjects', [CoursesController::class, 'getCourseSubjects']);

});

// Subjects Management

Route::middleware('auth:sanctum')->group(function () {
    Route::get('getsubjects', [SubjectsController::class, 'getSubjects']);
    Route::post('addsubject', [SubjectsController::class, 'addSubject']);
    });




// Create User
// Route::post('createuser', [AccountsController::class, 'createUser']);
// Route::post('createadmin', [AccountsController::class, 'createAdminAccount']);
// Route::post('getusers', [AccountsController::class, 'getUsers']);
// Route::post('updateprofile', [AccountsController::class, 'updateProfile'])->middleware('auth:sanctum');

// // Admissions Management
// Route::post('getadmissions', [AdmissionsController::class, 'getAdmissions']);
// Route::post('applyadmission', [AdmissionsController::class, 'applyAdmission'])->middleware('auth:sanctum');
// Route::post('approveadmission/{id}', [AdmissionsController::class, 'approveAdmission'])->middleware('auth:sanctum');
// Route::post('rejectadmission/{id}', [AdmissionsController::class, 'rejectAdmission'])->middleware('auth:sanctum');

// //Enrellments Management
// Route::middleware('auth:sanctum')->group(function () {
//     Route::get('listenrollments', [EnrollmentsController::class, 'listEnrollments']);
//     Route::post('storeenrollment', [EnrollmentsController::class, 'storeEnrollment']);
//     Route::post('updateenrollment/{id}', [EnrollmentsController::class, 'updateEnrollment']);
//     Route::post('deleteenrollment/{id}', [EnrollmentsController::class, 'deleteEnrollment']);
//     Route::post('restoreenrollment/{id}', [EnrollmentsController::class, 'restoreEnrollment']);
// });





// // Account Registration and Verification
// Route::post('register', [AccountsController::class, 'registerAccount']);
// Route::post('verifyaccount', [AccountsController::class, 'verifyAccount']);

// // School Campus Management
// Route::middleware('auth:sanctum')->group(function (){
 
//  Route::get('getcampuses', [SchoolCampusController::class, 'getCampuses']);   
//  Route::post('addcampus', [SchoolCampusController::class, 'addCampus']);

// });


// // Account Management
// Route::middleware('auth:sanctum')->group(function () {
//     Route::get('getaccounts', [AccountsController::class, 'getAccounts']);
//     Route::post('addaccount', [AccountsController::class, 'adminCreateAccount']);
//     Route::get('getprofile', [AccountsController::class, 'getProfile']);
//     Route::post('changeprofile', [AccountsController::class, 'changeProfile']);
//     Route::post('changepassword', [AccountsController::class, 'changePassword']);
//     Route::post('deleteaccount', [AccountsController::class, 'deleteAccount']);
//     Route::get('restoreaccount', [AccountsController::class, 'restoreAccount']);
// });

// // User Types Management
// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('createusertype', [UserTypesController::class, 'createUserType']);
//     Route::post('updateusertype/{id}', [UserTypesController::class, 'updateUserType']);
//     Route::post('deleteusertype/{id}', [UserTypesController::class, 'deleteUserType']);
//     Route::post('restoreusertype/{id}', [UserTypesController::class, 'restoreUserType']);
// });



//   // Courses Management
// Route::middleware('auth:sanctum')->group(function () {
    Route::post('addcourse', [CoursesController::class, 'addCourse']);
//     Route::get('getcourses', [CoursesController::class, 'getCourses']);
//     Route::post('updatecourse/{id}', [CoursesController::class, 'updateCourse']);
//     Route::post('deletecourse/{id}', [CoursesController::class, 'deleteCourse']);
//     Route::post('restorecourse/{id}', [CoursesController::class, 'restoreCourse']);
// });

// // Enrollments Management
// Route::middleware('auth:sanctum')->group(function () {
//     Route::get('getenrollments', [EnrollmentsController::class, 'getEnrollments']);
// });


// // School Years Management
// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('addschoolyear', [SchoolYearsController::class, 'createSchoolYear']);
//     Route::get('getschoolyears', [SchoolYearsController::class, 'getSchoolYears']);
//     Route::post('updateschoolyear/{id}', [SchoolYearsController::class, 'updateSchoolYear']);
//     Route::post('deleteschoolyear/{id}', [SchoolYearsController::class, 'deleteSchoolYear']);
//     Route::post('restoreschoolyear/{id}', [SchoolYearsController::class, 'restoreSchoolYear']);
// });

// // Sections Management
// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('addsection', [SectionsController::class, 'addSection']);
//     Route::get('getsections', [SectionsController::class, 'getSections']);
//     Route::post('updatesection/{id}', [SectionsController::class, 'updateSection']);
//     Route::post('deletesection/{id}', [SectionsController::class, 'deleteSection']);
//     Route::post('restoresection/{id}', [SectionsController::class, 'restoreSection']);
// });
