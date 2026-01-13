<?php

namespace AyupCreative\EventLog\Tests\Integration;

use AyupCreative\EventLog\EventLogger;
use AyupCreative\EventLog\Models\EventLog;
use AyupCreative\EventLog\Tests\Models\DummyBook;
use AyupCreative\EventLog\Tests\Models\DummyUser;
use AyupCreative\EventLog\Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use AyupCreative\EventLog\Jobs\WriteEventLogJob;

class EventLoggerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_it_writes_asynchronously(): void
    {
        $subject = new DummyBook();
        $subject->id = 1;
        
        EventLogger::log('test.event', $subject);
        
        Queue::assertPushed(WriteEventLogJob::class);
    }

    public function test_get_for_returns_events_where_model_is_subject(): void
    {
        $user = DummyUser::create(['name' => 'Subject User']);
        
        $log = EventLog::create([
            'event' => 'user.created',
            'subject_type' => $user::class,
            'subject_id' => $user->id,
            'correlation_id' => 'corr-1',
            'idempotency_key' => 'key-1',
        ]);

        $events = EventLogger::getFor($user);

        $this->assertCount(1, $events);
        $this->assertTrue($events->first()->is($log));
    }

    public function test_get_for_returns_events_where_model_is_related(): void
    {
        $user = DummyUser::create(['name' => 'Related User']);
        $book = DummyBook::create(['title' => 'Related Book']);
        
        $log = EventLog::create([
            'event' => 'book.created',
            'subject_type' => $book::class,
            'subject_id' => $book->id,
            'correlation_id' => 'corr-2',
            'idempotency_key' => 'key-2',
        ]);

        $log->relations()->create([
            'related_type' => $user::class,
            'related_id' => $user->id,
        ]);

        $events = EventLogger::getFor($user);

        $this->assertCount(1, $events);
        $this->assertTrue($events->first()->is($log));
    }

    public function test_get_for_returns_combined_results_latest_first(): void
    {
        $user = DummyUser::create(['name' => 'Multi User']);
        
        // Subject event
        $log1 = EventLog::create([
            'event' => 'user.updated',
            'subject_type' => $user::class,
            'subject_id' => $user->id,
            'correlation_id' => 'corr-3',
            'idempotency_key' => 'key-3',
        ]);
        $log1->created_at = now()->subDay();
        $log1->save();

        // Related event
        $log2 = EventLog::create([
            'event' => 'book.assigned',
            'subject_type' => DummyBook::class,
            'subject_id' => 1,
            'correlation_id' => 'corr-4',
            'idempotency_key' => 'key-4',
        ]);
        $log2->created_at = now();
        $log2->save();
        $log2->relations()->create([
            'related_type' => $user::class,
            'related_id' => $user->id,
        ]);

        $events = EventLogger::getFor($user);

        $this->assertCount(2, $events);
        $this->assertEquals($log2->id, $events->first()->id);
    }
}
