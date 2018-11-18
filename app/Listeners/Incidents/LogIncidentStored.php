<?php

namespace App\Listeners\Incidents;

use App;
use App\Models\Incident;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Log;

/**
 * Class LogIncidentStored
 * @package App\Listeners\Incidents
 */
class LogIncidentStored
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
        IncidentLogger::stored($event);
    }
}
