<?php

namespace AyupCreative\EventLog\Tests\Unit;

use AyupCreative\EventLog\Support\EventContext;
use AyupCreative\EventLog\Support\WithEventTransaction;
use AyupCreative\EventLog\Tests\UnitTestCase;
use Illuminate\Support\Facades\DB;

class WithEventTransactionTest extends UnitTestCase
{
    public function test_it_wraps_in_database_transaction_and_sets_event_context(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->assertNull(EventContext::transactionId());

        WithEventTransaction::run(function () {
            $this->assertNotNull(EventContext::transactionId());
            return 'result';
        });

        $this->assertNull(EventContext::transactionId());
    }

    public function test_it_clears_transaction_context_on_exception(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                try {
                    return $callback();
                } catch (\Exception $e) {
                    throw $e;
                }
            });

        try {
            WithEventTransaction::run(function () {
                $this->assertNotNull(EventContext::transactionId());
                throw new \Exception('fail');
            });
        } catch (\Exception $e) {
            $this->assertSame('fail', $e->getMessage());
        }

        $this->assertNull(EventContext::transactionId());
    }
}
