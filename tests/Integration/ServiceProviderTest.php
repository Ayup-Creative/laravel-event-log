<?php

namespace AyupCreative\EventLog\Tests\Integration;

use AyupCreative\EventLog\Support\EventContext;
use AyupCreative\EventLog\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class ServiceProviderTest extends TestCase
{
    public function test_http_macro_with_event_context(): void
    {
        EventContext::setCorrelationId('test-correlation-id');

        Http::fake();

        Http::withEventContext()->get('https://example.com');

        Http::assertSent(function ($request) {
            return $request->header('X-Correlation-ID')[0] === 'test-correlation-id';
        });
    }
}
