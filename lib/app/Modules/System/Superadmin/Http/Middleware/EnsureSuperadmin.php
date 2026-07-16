<?php

namespace App\Modules\System\Superadmin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Package\Exception\AuthenticationException;
use Package\Exception\AuthorizationException;

/**
 * Restricts a route to platform superadmins — the SaaS operators who act across
 * all tenants. Identity is the same username allow-list AuthServiceProvider's
 * Gate::before uses (config('constants.administrator_usernames')), matched
 * directly here rather than via the Gate facade: Spatie registers a Gate::before
 * hook that queries a permissions table this project does not have, so any
 * Gate call would blow up. This is a per-platform operator role, distinct from
 * the per-tenant is_admin flag.
 *
 * Usage: ->middleware(['auth.tera', 'superadmin'])
 */
class EnsureSuperadmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('api')->user();

        if (! $user) {
            throw new AuthenticationException;
        }

        if (! $user->is_superadmin) {
            throw new AuthorizationException('Chỉ quản trị viên hệ thống mới có quyền thực hiện thao tác này.');
        }

        return $next($request);
    }
}
