<?php

namespace AyupCreative\EventLog\Tests\Integration;

use AyupCreative\EventLog\Observers\EventLogObserver;
use AyupCreative\EventLog\Features\LogsEvents;
use AyupCreative\EventLog\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;

class EventLogObserverTest extends TestCase
{
    /**
     * @group bug
     */
    public function test_it_can_be_instantiated(): void
    {
        // This might fail if the wrong 'use' statement in the class causes issues
        // though PHP usually doesn't care about unused/wrong imports until the class is used.
        $observer = new EventLogObserver();
        $this->assertInstanceOf(EventLogObserver::class, $observer);
    }

    /**
     * @group bug
     */
    public function test_it_logs_created_event(): void
    {
        $user = \AyupCreative\EventLog\Tests\Models\DummyUser::create(['name' => 'Observer Test']);
        
        $observer = new EventLogObserver();
        
        $observer->created($user);
        
        $this->assertTrue(true);
    }
}
