<?php

namespace AyupCreative\EventLog\Tests\Integration;

use AyupCreative\EventLog\Jobs\WriteEventLogJob;
use AyupCreative\EventLog\Support\EventContext;
use AyupCreative\EventLog\Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Database\Eloquent\Model;

class EventLogHelperTest extends TestCase
{
    /**
     * @group bug
     */
    public function test_event_log_dispatches_job(): void
    {
        Queue::fake();
        
        $subject = new \AyupCreative\EventLog\Tests\Models\DummyBook();
        $subject->id = 123;
        
        $related = new \AyupCreative\EventLog\Tests\Models\DummyUser();
        $related->id = 456;

        EventContext::setCorrelationId('test-corr');
        
        event_log('test.event', $subject, [$related]);

        Queue::assertPushed(WriteEventLogJob::class, function ($job) use ($subject, $related) {
            return $job->event === 'test.event' &&
                   $job->subjectType === get_class($subject) &&
                   $job->subjectId === 123 &&
                   $job->correlationId === 'test-corr' &&
                   count($job->related) === 1;
        });
    }
}
