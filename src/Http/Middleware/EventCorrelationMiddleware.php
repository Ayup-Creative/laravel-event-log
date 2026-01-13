<?php

namespace AyupCreative\EventLog\Http\Middleware;

use AyupCreative\EventLog\Support\EventContext;
use Illuminate\Support\Str;

/**
 * Class EventCorrelationMiddleware
 *
 * Captures or generates a Correlation ID for every incoming request.
 * Ensures the ID is propagated to the EventContext and returned in the response headers.
 */
class EventCorrelationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        // Capture correlation ID from header or generate a new one.
        EventContext::setCorrelationId(
            $correlationId = $request->header('X-Correlation-ID')
                ?? (string) Str::uuid()
        );

        $response = $next($request);

        // Ensure the correlation ID is returned in the response for traceability.
        if (method_exists($response, 'header')) {
            $response->header('X-Correlation-ID', $correlationId);
        }

        return $response;
    }
}
