<?php

use Illuminate\Http\Request;

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

Route::domain('{tenant}.' . env('APP_DOMAIN'))->group(function () {
    Route::group(['middleware' => ['tenant','tenancy.enforce']], function () {
        Route::group(['prefix' => 'v1','middleware' => 'auth:api'], function () {

            //Subject lesson calculator
            Route::post('lessons/subject/{subject}/calculate', 'Tenant\SubjectLessonsCalculateController@store');

            //Available users
            Route::get('/available-users/{jobType?}', 'Tenant\AvailableUsersController@index');

            // Job substitutions
            Route::put('/job/{job}/substitution', 'Tenant\JobSubstitutionsController@update');
            Route::post('/job/{job}/substitution', 'Tenant\JobSubstitutionsController@store');
            Route::delete('/job/{job}/substitution/{user}', 'Tenant\JobSubstitutionsController@destroy');
            Route::delete('/job/{job}/substitutions', 'Tenant\JobSubstitutionsController@destroyAll');

            //Propose free user names
            Route::get('/proposeFreeUserName/{name}/{sn1}', 'Tenant\ProposeFreeUsernameController@index');

            // Teachers
            Route::get('/teachers', 'Tenant\TeachersController@index');
            Route::post('/teachers', 'Tenant\TeachersController@store');
            Route::delete('/teachers/{teacher}', 'Tenant\TeachersController@destroy');

            // Finish teacher add
            Route::post('/teacher/finish_add', 'Tenant\TeacherFinishAddController@store');

            // Approved teachers
            Route::post('/approved_teacher', 'Tenant\ApprovedTeacherController@store');
            Route::delete('/approved_teacher/{user}', 'Tenant\ApprovedTeacherController@destroy');

            // Logged user teacher
            Route::get('/teacher', 'Tenant\LoggedUserTeacherController@show');


            //Available teacher code:
            Route::get('/teacher/available_code', 'Tenant\TeacherAvailableCodeController@show');

            // User Person
            Route::post('/user_person', 'Tenant\UserPersonController@store');
            Route::delete('/user_person/{user}', 'Tenant\UserPersonController@destroy');

            // USERS
            Route::put('/user', 'Tenant\LoggedUserController@update');
            Route::get('/users', 'Tenant\UsersController@index');
            Route::post('/users', 'Tenant\UsersController@store');
            Route::delete('/users/{user}', 'Tenant\UsersController@destroy');
            Route::get('/users/{user}', 'Tenant\UsersController@get');

            //GET USER BY EMAIL
            Route::get('/users/email/{email}', 'Tenant\UserEmailsController@get');

            //GET USER BY name
            Route::get('/users/name/{name}', 'Tenant\UserNamesController@get');

            // Available users
            // TODO: UMMMMM
//            Route::get('/users/available', 'Tenant\UsersAvailableController@index');

            //Pending teachers
            Route::get('/pending_teachers', 'Tenant\PendingTeachersController@index');
            Route::delete('/pending_teacher/{teacher}', 'Tenant\PendingTeachersController@destroy');

            //Teacher photos
            Route::post('/teachers_photos', 'Tenant\TeachersPhotosController@store');

            Route::get('/unassigned_teacher_photo', 'Tenant\UnassignedTeacherPhotoController@index');
            Route::post('/unassigned_teacher_photo', 'Tenant\UnassignedTeacherPhotoController@store');

            Route::delete('/unassigned_teacher_photo/{photoslug}', 'Tenant\UnassignedTeacherPhotoController@destroy');
            Route::post('/unassigned_teacher_photos', 'Tenant\UnassignedTeacherPhotosController@store');
            Route::delete('/unassigned_teacher_photos/{photoslug}', 'Tenant\UnassignedTeacherPhotosController@destroy');
            Route::delete('/unassigned_teacher_photos', 'Tenant\UnassignedTeacherPhotosController@destroyAll');

            Route::put('/teacher_photo/{photoslug}', 'Tenant\TeacherPhotoController@edit');

            //Assign available teacher photo to teacher
            Route::post('/teacher/{user}/photo','Tenant\AssignedTeacherPhotoController@store');
            //Assign al available teacher photos to teachers automatically
            Route::post('/teachers/photos','Tenant\AssignedTeacherPhotoController@storeAll');

            //UnAssign available teacher photo to teacher
            Route::delete('/teacher/{user}/photo','Tenant\AssignedTeacherPhotoController@delete');

            //User photos
            Route::post('/user/{user}/photo','Tenant\UserPhotoController@store');
            Route::delete('/user/{user}/photo','Tenant\UserPhotoController@destroy');

            //Jobs
            Route::get('/jobs', 'Tenant\JobsController@index');
            Route::post('/jobs', 'Tenant\JobsController@store');
            Route::put('/jobs/{job}', 'Tenant\JobsController@update');
            Route::delete('/jobs/{job}', 'Tenant\JobsController@destroy');
            Route::get('/jobs/nextAvailableCode', 'Tenant\JobsController@nextAvailableCode');

            //Employee
            Route::post('/employee', 'Tenant\EmployeeController@store');
            Route::delete('/employee/{employee}', 'Tenant\EmployeeController@destroy');

            // Google Apps/Gsuite groups
            Route::get('/gsuite/groups', 'Tenant\GoogleGroupsController@index');
            Route::post('/gsuite/groups', 'Tenant\GoogleGroupsController@store');
            Route::delete('/gsuite/groups/{group}', 'Tenant\GoogleGroupsController@destroy');

            // Group members
            Route::get('/gsuite/groups/{group}/members', 'Tenant\GoogleGroupMembersController@index');

            //Google Apps/GSuite users
            Route::get('/gsuite/users', 'Tenant\GoogleUsersController@index');
            Route::post('/gsuite/users', 'Tenant\GoogleUsersController@store');
            Route::delete('/gsuite/users/{user}', 'Tenant\GoogleUsersController@destroy');

            //Associate Gsuite user to user
            Route::post('/user/{user}/gsuite','Tenant\UserGsuiteController@store');
            Route::put('/user/{user}/gsuite','Tenant\UserGsuiteController@edit');
            Route::delete('/user/{user}/gsuite','Tenant\UserGsuiteController@destroy');

            Route::post('/gsuite/users/search','Tenant\GoogleUsersSearchController@search');


            //Google Ldap users
            Route::get('/ldap/users', 'Tenant\LdapUsersController@index');
            Route::post('/ldap/users', 'Tenant\LdapUsersController@store');
//            Route::delete('/ldap/users/{user}', 'Tenant\LdapUsersController@destroy');

            //Google Suite watch users TODO
            Route::post('/gsuite/users/watch', 'Tenant\GoogleUsersWatchController@store');

            //Resend user email verification email
            Route::get('/email/resend/{user}', 'Auth\Tenant\VerificationController@resendUser');

            //Re(send) welcome user email
            Route::get('/email/welcome/{user}', 'Auth\Tenant\ForgotPasswordController@welcome');

            // INCIDENTS
            Route::get('/incidents', 'Tenant\Api\IncidentsController@index');
            Route::post('/incidents', 'Tenant\Api\IncidentsController@store');
            Route::get('/incidents/{incident}', 'Tenant\Api\IncidentsController@show');
            Route::delete('/incidents/{incident}', 'Tenant\Api\IncidentsController@destroy');

            //Closed incidents
            Route::post('/closed_incidents/{incident}', 'Tenant\Api\IncidentsClosedController@store');
            Route::delete('/closed_incidents/{incident}', 'Tenant\Api\IncidentsClosedController@destroy');

            //Incidents individual fields
            Route::put('/incidents/{incident}/subject','Tenant\Api\IncidentsSubjectController@update');
            Route::put('/incidents/{incident}/description','Tenant\Api\IncidentsDescriptionController@update');

            // Incident Replies
            Route::get('/incidents/{incident}/replies','Tenant\Api\IncidentRepliesController@index');
            Route::post('/incidents/{incident}/replies','Tenant\Api\IncidentRepliesController@store');
            Route::put('/incidents/{incident}/replies/{reply}','Tenant\Api\IncidentRepliesController@update');
            Route::delete('/incidents/{incident}/replies/{reply}','Tenant\Api\IncidentRepliesController@destroy');

            //BodyReplies
            Route::put('/replies/{reply}/body','Tenant\Api\RepliesBodyController@update');
//            $response = $this->json('PUT','/api/v1/replies/' . $reply->id . '/body',[

        });

        Route::group(['prefix' => 'v1'], function () {
            Route::get('/menu', 'Tenant\MenuController@index');

            Route::post('/add_teacher', 'Tenant\PendingTeachersController@store');

            Route::get('/provinces','Tenant\ProvincesController@index');
            Route::get('/localities','Tenant\LocalitiesController@index');
            Route::get('/locations','Tenant\LocalitiesController@index');

            // TODO remove
//            Route::get('/gsuite/test_connection','Tenant\GoogleSuiteTestConnectionController@index');
        });
    });
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'v1','middleware' => 'auth:api'], function () {
    Route::get('tenant','UserTenantController@index');
    Route::post('tenant','UserTenantController@store');
    Route::delete('tenant/{tenant}','UserTenantController@destroy');
    Route::put('tenant/{tenant}/name','UserTenantNameController@update');
    Route::put('tenant/{tenant}/subdomain','UserTenantSubdomainController@update');
    Route::put('tenant/{tenant}/password','UserTenantPasswordController@update');

    Route::get('tenant/{tenant}/test','UserTenantTestController@index');
    Route::post('tenant/{tenant}/test-user','UserTenantTestAdminUserController@index');

});

