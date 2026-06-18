<?php

namespace App\Modules\System\ActivityLog\Events;

use App\Modules\System\ActivityLog\Listeners\WriteActivityLog;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired whenever a business action should be audited. A queued listener persists
 * it (spec 028 §VIII), keeping logging off the request's transaction path.
 *
 * @see WriteActivityLog
 */
class ActivityLogged
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $attributes  sys_activity_logs column values
     */
    public function __construct(public array $attributes) {}
}
