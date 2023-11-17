<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests as BaseThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

class ThrottleRequests extends BaseThrottleRequests
{
    /**
     * Handle an incoming request.
     *
     * @param int|string $maxAttempts
     * @param float|int $decayMinutes
     * @param mixed $prefix
     * @param mixed $request
     *
     * @throws \Illuminate\Http\Exceptions\ThrottleRequestsException
     */
    public function handle($request, Closure $next, $maxAttempts = null, $decayMinutes = 1, $prefix = ''): Response
    {
        $maxAttempts = $maxAttempts ?: config('local.api_rate_limit');

        // If not testing environment, then delegate to original logic in parent class.
        if (app()->environment() !== 'testing') {
            return parent::handle($request, $next, $maxAttempts, $decayMinutes, $prefix);
        }

        // Don't rate limit when not in a testing environment.
        $key = $this->resolveRequestSignature($request);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }
}
