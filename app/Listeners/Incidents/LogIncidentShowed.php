<?php

namespace App\Listeners\Incidents;

use App;

/**
 * Class LogIncidentShowed
 * @package App\Listeners\Incidents
 */
class LogIncidentShowed
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        if (App::environment('testing')) return;
        IncidentLogger::showed($event);
    }
}
