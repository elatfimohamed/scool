<?php

namespace App\Listeners\Incidents;

use App\Mail\Incidents\IncidentCommentAdded;
use App\Mail\Incidents\IncidentUntagged;
use App\Models\Setting;
use Mail;

/**
 * Class SendIncidentTagRemovedEmail.
 *
 * @package App\Listeners\Incidents
 */
class SendIncidentTagRemovedEmail
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        Mail::to($event->incident->user)->cc(Setting::get('incidents_manager_email'))->queue(new IncidentUntagged($event->incident));
    }
}
