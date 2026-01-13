<?php

namespace AyupCreative\EventLog\Tests\Integration;

use AyupCreative\EventLog\Models\EventLog;
use AyupCreative\EventLog\Models\EventLogRelation;
use AyupCreative\EventLog\Tests\Models\DummyUser;
use AyupCreative\EventLog\Tests\TestCase;

class ModelsTest extends TestCase
{
    public function test_event_log_relationships(): void
    {
        $user = DummyUser::create(['name' => 'Causer']);
        $subject = DummyUser::create(['name' => 'Subject']);
        
        $log = EventLog::create([
            'event' => 'test.event',
            'subject_type' => $subject::class,
            'subject_id' => $subject->id,
            'causer_id' => $user->id,
            'causer_type' => 'user',
            'correlation_id' => 'corr',
            'idempotency_key' => 'key',
        ]);

        $relation = $log->relations()->create([
            'related_type' => $user::class,
            'related_id' => $user->id,
        ]);

        $this->assertInstanceOf(DummyUser::class, $log->subject);
        $this->assertTrue($log->subject->is($subject));
        
        $this->assertInstanceOf(DummyUser::class, $log->causer);
        $this->assertTrue($log->causer->is($user));
        
        $this->assertCount(1, $log->relations);
        $this->assertTrue($log->relations->first()->is($relation));
        
        $this->assertEquals($log->id, $relation->event_log_id);
        $this->assertInstanceOf(EventLog::class, $relation->event);
        $this->assertTrue($relation->event->is($log));
        
        $this->assertInstanceOf(DummyUser::class, $relation->related);
        $this->assertTrue($relation->related->is($user));
    }

    public function test_event_log_related_morph_to_many(): void
    {
        $user = DummyUser::create(['name' => 'Related User']);
        $log = EventLog::create([
            'event' => 'test.event',
            'subject_type' => DummyUser::class,
            'subject_id' => 1,
            'correlation_id' => 'corr2',
            'idempotency_key' => 'key2',
        ]);

        $log->relations()->create([
            'related_type' => $user::class,
            'related_id' => $user->id,
        ]);

        $this->assertCount(1, $log->related);
        $this->assertTrue($log->related->first()->is($user));
    }

    public function test_causer_label(): void
    {
        $log = new EventLog(['causer_type' => 'user']);
        $user = new DummyUser(['name' => 'John']);
        $log->setRelation('causer', $user);
        $this->assertSame('John', $log->causerLabel());

        $log->causer_type = 'system';
        $this->assertSame('System', $log->causerLabel());

        $log->causer_type = 'job';
        $this->assertSame('Automated process', $log->causerLabel());

        $log->causer_type = 'webhook';
        $this->assertSame('External service', $log->causerLabel());

        $log->causer_type = 'unknown';
        $this->assertSame('Unknown', $log->causerLabel());
        
        $log->causer_type = 'user';
        $log->setRelation('causer', null);
        $this->assertSame('Unknown user', $log->causerLabel());
    }
}
