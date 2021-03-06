<?php

namespace App\Listeners;

use App\Mail\GoogleUserNotificationReceived;
use Mail;

/**
 * Class SendGoogleUserNotificationsReceivedEmail.
 *
 * @package App\Listeners
 */
class SendGoogleUserNotificationReceivedEmail
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        if (config('scool.gsuite_notifications_send_email')) {
            Mail::to(config('scool.gsuite_notifications_email'))->send(new GoogleUserNotificationReceived($event->request));
        }
    }
}
