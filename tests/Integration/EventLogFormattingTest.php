<?php

namespace AyupCreative\EventLog\Tests\Integration;

use AyupCreative\EventLog\Facades\EventLog;
use AyupCreative\EventLog\Models\EventLog as EventLogModel;
use AyupCreative\EventLog\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EventLogFormattingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_format_an_event_name_into_a_human_readable_string()
    {
        // 1. Arrange: Create an event log
        $eventLog = EventLogModel::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'event' => 'user.created',
            'subject_type' => 'App\Models\User',
            'subject_id' => 1,
            'causer_id' => 1,
            'causer_type' => 'user',
            'correlation_id' => \Illuminate\Support\Str::uuid(),
            'idempotency_key' => 'key-1',
        ]);

        // 2. Act: By default, it should return the event name
        $this->assertEquals('user.created', $eventLog->description);

        // 3. Act: Register a formatter
        EventLog::formatEventsWith(function ($eventLog) {
            return match ($eventLog->event) {
                'user.created' => 'A new user was created',
                default => $eventLog->event,
            };
        });

        // 4. Assert: It should now use the custom formatter
        $this->assertEquals('A new user was created', $eventLog->description);
    }

    public function test_it_can_use_context_in_the_formatter()
    {
         // 1. Arrange: Create an event log with metadata
        $eventLog = EventLogModel::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'event' => 'payment.failed',
            'subject_type' => 'App\Models\Payment',
            'subject_id' => 1,
            'correlation_id' => \Illuminate\Support\Str::uuid(),
            'idempotency_key' => 'key-2',
        ]);

        $eventLog->metadata()->create([
            'key' => 'error_reason',
            'value' => 'Insufficient funds',
        ]);

        // 2. Act: Register a formatter that uses metadata
        EventLog::formatEventsWith(function ($eventLog) {
            if ($eventLog->event === 'payment.failed') {
                $reason = $eventLog->meta->error_reason;
                return "Payment failed because: {$reason}";
            }
            return $eventLog->event;
        });

        // 3. Assert: It should use the metadata in the formatted string
        $this->assertEquals('Payment failed because: Insufficient funds', $eventLog->description);
    }

    public function test_it_can_use_a_class_based_formatter()
    {
        // 1. Arrange: Create an event log
        $eventLog = EventLogModel::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'event' => 'user.deleted',
            'subject_type' => 'App\Models\User',
            'subject_id' => 1,
            'correlation_id' => \Illuminate\Support\Str::uuid(),
            'idempotency_key' => 'key-3',
        ]);

        // 2. Act: Register a class name as formatter
        EventLog::formatEventsWith(DummyEventFormatter::class);

        // 3. Assert: It should use the class to format
        $this->assertEquals('Formatted by DummyEventFormatter: user.deleted', $eventLog->description);
    }

    public function test_it_can_use_a_formatter_from_config()
    {
        // 1. Arrange: Create an event log
        $eventLog = EventLogModel::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'event' => 'user.restored',
            'subject_type' => 'App\Models\User',
            'subject_id' => 1,
            'correlation_id' => \Illuminate\Support\Str::uuid(),
            'idempotency_key' => 'key-4',
        ]);

        // Clear any registered formatter to fallback to config
        $logger = app('event-log');
        $reflection = new \ReflectionClass($logger);
        $property = $reflection->getProperty('eventFormatter');
        $property->setAccessible(true);
        $property->setValue($logger, null);

        config(['event-log.event_formatter' => DummyEventFormatter::class]);

        // 2. Assert: It should use the config formatter
        $this->assertEquals('Formatted by DummyEventFormatter: user.restored', $eventLog->description);
    }
}

class DummyEventFormatter
{
    public function __invoke($eventLog)
    {
        return "Formatted by DummyEventFormatter: {$eventLog->event}";
    }
}
