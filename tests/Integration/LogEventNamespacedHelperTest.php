<?php

namespace AyupCreative\EventLog\Tests\Integration;

use AyupCreative\EventLog\Jobs\WriteEventLogJob;
use AyupCreative\EventLog\Support\EventContext;
use AyupCreative\EventLog\Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use function AyupCreative\EventLog\log_event;

enum TestEvent: string
{
    case CREATED = 'test.created';
    case DELETED = 'test.deleted';
}

class LogEventNamespacedHelperTest extends TestCase
{
    public function test_log_event_dispatches_job_with_string_event(): void
    {
        Queue::fake();

        $subject = new \AyupCreative\EventLog\Tests\Models\DummyBook();
        $subject->id = 123;

        $related = new \AyupCreative\EventLog\Tests\Models\DummyUser();
        $related->id = 456;

        EventContext::setCorrelationId('test-corr');

        log_event('test.event', $subject, [$related], null, ['foo' => 'bar']);

        Queue::assertPushed(WriteEventLogJob::class, function ($job) use ($subject, $related) {
            return $job->event === 'test.event' &&
                   $job->subjectType === get_class($subject) &&
                   $job->subjectId === 123 &&
                   $job->correlationId === 'test-corr' &&
                   count($job->related) === 1 &&
                   $job->metadata === ['foo' => 'bar'];
        });
    }

    public function test_log_event_dispatches_job_with_enum_event(): void
    {
        Queue::fake();

        $subject = new \AyupCreative\EventLog\Tests\Models\DummyBook();
        $subject->id = 123;

        log_event(TestEvent::CREATED, $subject);

        Queue::assertPushed(WriteEventLogJob::class, function ($job) use ($subject) {
            return $job->event === 'test.created' &&
                   $job->subjectType === get_class($subject) &&
                   $job->subjectId === 123;
        });
    }

    public function test_log_event_respects_custom_queue_config(): void
    {
        config(['event-log.queue' => 'custom-queue']);
        Queue::fake();

        $subject = new \AyupCreative\EventLog\Tests\Models\DummyBook();
        $subject->id = 123;

        log_event('test.event', $subject);

        Queue::assertPushed(WriteEventLogJob::class, function ($job) {
            return $job->queue === 'custom-queue';
        });
    }
}
