<?php

namespace Package\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Package\Exception\AuthenticationException;
use Package\Exception\AuthorizationException;

class Authentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!Auth::guard('api')->user()) {
            throw new AuthenticationException();
        }

        $role_id = Auth::guard('api')->user()->role_id;
        $status = Auth::guard('api')->user()->status;

        if ($status != 1) {
            throw new AuthorizationException("Tài khoản của bạn hiện tạm thời bị khoá, vui lòng liên hệ admin");
        }

        if (!$role_id) {
            throw new AuthorizationException();
        }

        return $next($request);
    }
}
