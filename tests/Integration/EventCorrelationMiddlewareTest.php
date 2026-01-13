<?php

namespace AyupCreative\EventLog\Tests\Integration;

use AyupCreative\EventLog\Http\Middleware\EventCorrelationMiddleware;
use AyupCreative\EventLog\Support\EventContext;
use AyupCreative\EventLog\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EventCorrelationMiddlewareTest extends TestCase
{
    public function test_it_sets_correlation_id_from_header(): void
    {
        $id = 'header-corr-id';
        $request = new Request();
        $request->headers->set('X-Correlation-ID', $id);

        $middleware = new EventCorrelationMiddleware();

        $middleware->handle($request, function ($req) use ($id) {
            $this->assertSame($id, EventContext::correlationId());
            return new Response();
        });
    }

    public function test_it_adds_correlation_id_to_response_header(): void
    {
        $request = new Request();
        $middleware = new EventCorrelationMiddleware();

        $response = $middleware->handle($request, function ($req) {
            return new Response();
        });

        $this->assertTrue($response->headers->has('X-Correlation-ID'));
        $this->assertSame(EventContext::correlationId(), $response->headers->get('X-Correlation-ID'));
    }
}
