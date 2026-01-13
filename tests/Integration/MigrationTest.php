<?php

namespace AyupCreative\EventLog\Tests\Integration;

use AyupCreative\EventLog\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class MigrationTest extends TestCase
{
    /**
     * @group bug
     */
    public function test_migrations_can_be_run(): void
    {
        // This test will fail if the migration is broken.
        // Known bug: Duplicate index on event_log_relations table.
        $this->assertTrue(Schema::hasTable('event_logs'));
        $this->assertTrue(Schema::hasTable('event_log_relations'));
    }
}
