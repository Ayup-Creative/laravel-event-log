<?php

namespace AyupCreative\EventLog\Http\Middleware;

use AyupCreative\EventLog\Support\EventContext;
use Illuminate\Support\Str;

class EventCorrelationMiddleware
{
    public function handle($request, $next)
    {
        EventContext::setCorrelationId(
            $correlationId = $request->header('X-Correlation-ID')
                ?? (string) Str::uuid()
        );

        $response = $next($request);

        if (method_exists($response, 'header')) {
            $response->header('X-Correlation-ID', $correlationId);
        }

        return $response;
    }
}
