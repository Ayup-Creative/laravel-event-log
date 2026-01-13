<?php

namespace AyupCreative\EventLog\Tests\Unit;

use AyupCreative\EventLog\Support\Idempotency;
use AyupCreative\EventLog\Tests\UnitTestCase;

class IdempotencyTest extends UnitTestCase
{
    public function test_it_generates_deterministic_key(): void
    {
        $key1 = Idempotency::key('corr-1', 'tx-1', 'test.event', 'Subject', 1);
        $key2 = Idempotency::key('corr-1', 'tx-1', 'test.event', 'Subject', 1);
        $key3 = Idempotency::key('corr-1', null, 'test.event', 'Subject', 1);

        $this->assertSame($key1, $key2);
        $this->assertNotSame($key1, $key3);
        $this->assertSame(64, strlen($key1)); // SHA256
    }
}
