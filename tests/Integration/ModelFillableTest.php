<?php

namespace AyupCreative\EventLog\Tests\Integration;

use AyupCreative\EventLog\Models\EventLog;
use AyupCreative\EventLog\Tests\TestCase;

class ModelFillableTest extends TestCase
{
    /**
     * @group bug
     */
    public function test_event_log_model_has_all_fillable_fields(): void
    {
        $model = new EventLog();
        $fillable = $model->getFillable();

        $this->assertContains('correlation_id', $fillable, 'correlation_id should be fillable');
        $this->assertContains('transaction_id', $fillable, 'transaction_id should be fillable');
        $this->assertContains('idempotency_key', $fillable, 'idempotency_key should be fillable');
    }
}
