<?php

namespace AyupCreative\EventLog\Tests\Unit;

use AyupCreative\EventLog\Support\EventContext;
use AyupCreative\EventLog\Tests\UnitTestCase;
use Illuminate\Support\Str;

class EventContextTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset static properties between tests
        $ref = new \ReflectionClass(EventContext::class);
        $correlationId = $ref->getProperty('correlationId');
        $correlationId->setAccessible(true);
        $correlationId->setValue(null);

        $transactionId = $ref->getProperty('transactionId');
        $transactionId->setAccessible(true);
        $transactionId->setValue(null);
    }

    public function test_it_generates_a_correlation_id_if_none_set(): void
    {
        $id = EventContext::correlationId();

        $this->assertTrue(Str::isUuid($id));
        $this->assertSame($id, EventContext::correlationId());
    }

    public function test_it_can_set_and_get_correlation_id(): void
    {
        $id = (string) Str::uuid();
        EventContext::setCorrelationId($id);

        $this->assertSame($id, EventContext::correlationId());
    }

    public function test_it_can_begin_and_clear_transaction(): void
    {
        $this->assertNull(EventContext::transactionId());

        EventContext::beginTransaction();
        $id = EventContext::transactionId();

        $this->assertTrue(Str::isUuid($id));
        $this->assertSame($id, EventContext::transactionId());

        EventContext::clearTransaction();
        $this->assertNull(EventContext::transactionId());
    }
}
