<?php

namespace App\Listeners\Authentication;

use App;
use App\Models\Log;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Schema;

/**
 * Class LogLeaveImpersonation.
 * @package App\Listeners\Authentication
 */
class LogLeaveImpersonation
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
        if (App::environment('testing')) return;

        if (!Schema::hasTable('logs')) return;

        AuthenticationLogger::leaveImpersonation($event);

    }
}
