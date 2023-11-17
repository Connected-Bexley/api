<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OtpCodeSent
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('otp.user_id') && session()->has('otp.code')) {
            return $next($request);
        }

        return redirect(route('login'));
    }
}
