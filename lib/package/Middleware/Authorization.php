<?php

namespace Package\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Exception\AuthenticationException;
use Package\Exception\AuthorizationException;

/**
 * Permission guard.
 *
 * Usage: ->middleware('permission:business.list')
 *
 * Access rules (see business.md):
 *  - Super Admin / Admin (is_admin = true)  => full access, bypasses the check.
 *  - Other roles (e.g. Manager)             => allowed only when the role has
 *                                              the required permission granted.
 */
class Authorization
{
    public function handle($request, Closure $next, ?string $permission = null)
    {
        $user = Auth::guard('api')->user();

        if (! $user) {
            throw new AuthenticationException;
        }

        // No specific permission required.
        if (! $permission) {
            return $next($request);
        }

        // Super Admin / Admin have full access.
        if ($user->is_admin) {
            return $next($request);
        }

        if (! $this->roleHasPermission($user->role_id, $permission)) {
            throw new AuthorizationException;
        }

        return $next($request);
    }

    private function roleHasPermission($roleId, string $permission): bool
    {
        if (! $roleId) {
            return false;
        }

        return DB::table('role_has_permissions as rhp')
            ->join('sys_permissions as p', 'p.id', '=', 'rhp.permission_id')
            ->where('rhp.role_id', $roleId)
            ->where('p.code', $permission)
            ->where('p.is_active', true)
            ->exists();
    }
}
