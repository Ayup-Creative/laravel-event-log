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
        $this->assertTrue(Schema::hasTable('event_logs'));
        $this->assertTrue(Schema::hasTable('event_log_relations'));
        $this->assertTrue(Schema::hasTable('event_log_metadata'));
    }

    public function test_event_log_child_tables_use_event_log_id_foreign_keys(): void
    {
        $this->assertTrue(Schema::hasColumn('event_log_relations', 'event_log_id'));
        $this->assertFalse(Schema::hasColumn('event_log_relations', 'event_id'));

        $this->assertTrue(Schema::hasColumn('event_log_metadata', 'event_log_id'));
        $this->assertFalse(Schema::hasColumn('event_log_metadata', 'event_id'));
    }
}
