<?php

namespace Package\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Exception\AuthenticationException;
use Package\Exception\AuthorizationException;

class Authentication
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::guard('api')->user();

        if (! $user) {
            throw new AuthenticationException;
        }

        if (! $user->is_active) {
            throw new AuthorizationException('Tài khoản của bạn hiện tạm thời bị khoá, vui lòng liên hệ admin');
        }

        if (! $user->role_id) {
            throw new AuthorizationException;
        }

        // A superadmin-suspended tenant loses access to every endpoint.
        if ($user->business_id) {
            $businessStatus = DB::table('sys_business')
                ->where('id', $user->business_id)
                ->value('status');

            if ($businessStatus === 'suspended') {
                throw new AuthorizationException('Trung tâm của bạn đã bị tạm ngưng, vui lòng liên hệ để được hỗ trợ.');
            }
        }

        return $next($request);
    }
}
