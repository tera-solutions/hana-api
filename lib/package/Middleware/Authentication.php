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
        $user = Auth::guard('api')->user();

        if (!$user) {
            throw new AuthenticationException();
        }

        if (!$user->is_active) {
            throw new AuthorizationException("Tài khoản của bạn hiện tạm thời bị khoá, vui lòng liên hệ admin");
        }

        if (!$user->role_id) {
            throw new AuthorizationException();
        }

        return $next($request);
    }
}
