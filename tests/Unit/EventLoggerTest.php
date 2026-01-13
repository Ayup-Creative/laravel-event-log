<?php

namespace AyupCreative\EventLog\Tests\Unit;

use AyupCreative\EventLog\EventLogger;
use AyupCreative\EventLog\Contracts\EventModel;
use AyupCreative\EventLog\Tests\UnitTestCase;
use Mockery;

class EventLoggerTest extends UnitTestCase
{
    /**
     * @group bug
     */
    public function test_it_writes_asynchronously(): void
    {
        // This test demonstrates that EventLogger::log now dispatches a job
        \Illuminate\Support\Facades\Queue::fake();
        
        $subject = \Mockery::mock(\Illuminate\Database\Eloquent\Model::class);
        $subject->shouldReceive('getMorphClass')->andReturn('Subject');
        $subject->shouldReceive('getKey')->andReturn(1);
        
        EventLogger::log('test.event', $subject);
        
        \Illuminate\Support\Facades\Queue::assertPushed(\AyupCreative\EventLog\Jobs\WriteEventLogJob::class);
    }
}
