<?php

namespace App\Modules\System\ActivityLog\Listeners;

use App\Modules\System\ActivityLog\Events\ActivityLogged;
use App\Modules\System\ActivityLog\Services\ActivityLogService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Persists an audit entry off the request path. Queued so the write survives a
 * rolled-back business transaction (spec 028 BR-01) when run on an async worker.
 */
class WriteActivityLog implements ShouldQueue
{
    public function __construct(private ActivityLogService $service) {}

    public function handle(ActivityLogged $event): void
    {
        $this->service->write($event->attributes);
    }
}
