<?php

namespace App\Modules\System\ActivityLog\Support;

use App\Modules\System\ActivityLog\Events\ActivityLogged;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Entry point for recording audit logs (spec 028). Auto-fills actor and request
 * context, then dispatches ActivityLogged for the queued listener to persist.
 */
class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $attributes  module/entity/entity_id/action/old_data/new_data/changed_fields/description/status
     */
    public static function log(array $attributes): void
    {
        event(new ActivityLogged(array_merge(self::context(), $attributes)));
    }

    /**
     * @return array<string, mixed>
     */
    private static function context(): array
    {
        $request = request();
        $userId = Auth::guard('api')->id() ?? Auth::id();

        return [
            'user_id' => $userId,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'request_id' => $request?->header('X-Request-Id') ?? (string) Str::uuid(),
            'endpoint' => $request?->path(),
            'method' => $request?->method(),
            'status' => 'success',
        ];
    }
}
