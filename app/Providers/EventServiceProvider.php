<?php

namespace App\Providers;

use App\Events\GoogleInvalidUserNotificationReceived;
use App\Events\GoogleUserNotificationReceived;
use App\Events\Incidents\IncidentAssigned;
use App\Events\Incidents\IncidentClosed;
use App\Events\Incidents\IncidentDeleted;
use App\Events\Incidents\IncidentDesassigned;
use App\Events\Incidents\IncidentDescriptionUpdated;
use App\Events\Incidents\IncidentOpened;
use App\Events\Incidents\IncidentReplyAdded;
use App\Events\Incidents\IncidentReplyRemoved;
use App\Events\Incidents\IncidentReplyUpdated;
use App\Events\Incidents\IncidentShowed;
use App\Events\Incidents\IncidentStored;
use App\Events\Incidents\IncidentSubjectUpdated;
use App\Events\Incidents\IncidentTagAdded;
use App\Events\Incidents\IncidentTagRemoved;
use App\Events\TeacherPhotosZipUploaded;
use App\Events\TenantCreated;
use App\Listeners\Authentication\LogAttemptLoginUser;
use App\Listeners\Authentication\LogLeaveImpersonation;
use App\Listeners\Authentication\LogLogedOutUser;
use App\Listeners\Authentication\LogLoginUser;
use App\Listeners\Authentication\LogPasswordResetUser;
use App\Listeners\Authentication\LogRegisteredUser;
use App\Listeners\Authentication\LogTakeImpersonation;
use App\Listeners\Authentication\LogVerifiedUser;
use App\Listeners\CreateTenantDatabase;
use App\Listeners\Incidents\LogIncidentAssigned;
use App\Listeners\Incidents\LogIncidentClosed;
use App\Listeners\Incidents\LogIncidentDeleted;
use App\Listeners\Incidents\LogIncidentDesassigned;
use App\Listeners\Incidents\LogIncidentDescriptionUpdated;
use App\Listeners\Incidents\LogIncidentOpened;
use App\Listeners\Incidents\LogIncidentReplyAdded;
use App\Listeners\Incidents\LogIncidentReplyRemoved;
use App\Listeners\Incidents\LogIncidentReplyUpdated;
use App\Listeners\Incidents\LogIncidentShowed;
use App\Listeners\Incidents\LogIncidentStored;
use App\Listeners\Incidents\LogIncidentSubjectUpdated;
use App\Listeners\Incidents\LogIncidentTagAdded;
use App\Listeners\Incidents\LogIncidentTagRemoved;
use App\Listeners\Incidents\SendIncidentAssignedEmail;
use App\Listeners\Incidents\SendIncidentClosedEmail;
use App\Listeners\Incidents\SendIncidentCreatedEmail;
use App\Listeners\Incidents\SendIncidentDeletedEmail;
use App\Listeners\Incidents\SendIncidentDesassignedEmail;
use App\Listeners\Incidents\SendIncidentDescriptionUpdateEmail;
use App\Listeners\Incidents\SendIncidentOpenedEmail;
use App\Listeners\Incidents\SendIncidentReplyAddedEmail;
use App\Listeners\Incidents\SendIncidentSubjectUpdateEmail;
use App\Listeners\Incidents\SendIncidentTagAddedEmail;
use App\Listeners\Incidents\SendIncidentTagRemovedEmail;
use App\Listeners\SetLastLoginInfo;
use App\Listeners\SendGoogleInvalidUserNotificationReceivedEmail;
use App\Listeners\SendGoogleUserNotificationReceivedEmail;
use App\Listeners\SyncGoogleUsers;
use App\Listeners\UnzipTeacherPhotos;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;

/**
 * Class EventServiceProvider
 * @package App\Providers
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
//            AddRolesToRegisterUser::class -> TODO
            LogRegisteredUser::class
        ],
        Logout::class => [
            LogLogedOutUser::class
        ],
        Login::class => [
            SetLastLoginInfo::class,
            LogLoginUser::class
        ],
        Failed::class => [
            LogAttemptLoginUser::class
        ],
        PasswordReset::class => [
            LogPasswordResetUser::class
        ],
        Verified::class => [
            LogVerifiedUser::class
        ],

        // Impersonate
        TakeImpersonation::class => [
            LogTakeImpersonation::class
        ],
        LeaveImpersonation::class => [
            LogLeaveImpersonation::class
        ],

        // Incidents
        IncidentStored::class => [
            LogIncidentStored::class,
            SendIncidentCreatedEmail::class
        ],

        IncidentClosed::class => [
            LogIncidentClosed::class,
            SendIncidentClosedEmail::class
        ],

        IncidentOpened::class => [
            LogIncidentOpened::class,
            SendIncidentOpenedEmail::class
        ],

        IncidentShowed::class => [
            LogIncidentShowed::class
        ],

        IncidentDeleted::class => [
            LogIncidentDeleted::class,
            SendIncidentDeletedEmail::class
        ],

        IncidentDescriptionUpdated::class => [
            LogIncidentDescriptionUpdated::class,
            SendIncidentDescriptionUpdateEmail::class
        ],

        IncidentSubjectUpdated::class => [
            LogIncidentSubjectUpdated::class,
            SendIncidentSubjectUpdateEmail::class
        ],

        IncidentReplyAdded::class => [
            LogIncidentReplyAdded::class,
            SendIncidentReplyAddedEmail::class
        ],

        IncidentTagAdded::class => [
            LogIncidentTagAdded::class,
            SendIncidentTagAddedEmail::class
        ],

        IncidentTagRemoved::class => [
            LogIncidentTagRemoved::class,
            SendIncidentTagRemovedEmail::class
        ],

        IncidentReplyUpdated::class => [
            LogIncidentReplyUpdated::class
        ],

        IncidentReplyRemoved::class => [
            LogIncidentReplyRemoved::class
        ],

        IncidentAssigned::class => [
            LogIncidentAssigned::class,
            SendIncidentAssignedEmail::class
        ],

        IncidentDesassigned::class => [
            LogIncidentDesassigned::class,
            SendIncidentDesassignedEmail::class
        ],

        // TENANTS
        TenantCreated::class => [
            CreateTenantDatabase::class,
        ],
        TeacherPhotosZipUploaded::class => [
            UnzipTeacherPhotos::class
        ],

        // GOOGLE
        GoogleUserNotificationReceived::class => [
            SendGoogleUserNotificationReceivedEmail::class,
            SyncGoogleUsers::class
        ],
        GoogleInvalidUserNotificationReceived::class => [
            SendGoogleInvalidUserNotificationReceivedEmail::class,
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
