<?php

namespace AyupCreative\EventLog\Tests\Integration;

use AyupCreative\EventLog\Models\EventLog;
use AyupCreative\EventLog\Tests\TestCase;
use Illuminate\Support\Str;

class EventLogMetadataTest extends TestCase
{
    public function test_it_can_access_metadata_via_property_on_metadata_relationship(): void
    {
        $event = EventLog::create([
            'event' => 'test.event',
            'subject_type' => 'test',
            'subject_id' => '1',
            'correlation_id' => (string) Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $event->metadata()->create([
            'key' => 'description',
            'value' => 'This is a description',
        ]);

        $this->assertEquals('This is a description', $event->metadata->description);
    }

    public function test_it_can_access_metadata_via_meta_property(): void
    {
        $event = EventLog::create([
            'event' => 'test.event',
            'subject_type' => 'test',
            'subject_id' => '1',
            'correlation_id' => (string) Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $event->metadata()->create([
            'key' => 'reason',
            'value' => 'Some reason',
        ]);

        $this->assertEquals('Some reason', $event->meta->reason);
    }

    public function test_meta_property_returns_all_metadata_when_no_property_passed(): void
    {
        $event = EventLog::create([
            'event' => 'test.event',
            'subject_type' => 'test',
            'subject_id' => '1',
            'correlation_id' => (string) Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $event->metadata()->create([
            'key' => 'key1',
            'value' => 'value1',
        ]);

        $event->metadata()->create([
            'key' => 'key2',
            'value' => 'value2',
        ]);

        $this->assertCount(2, $event->meta);
    }
}
