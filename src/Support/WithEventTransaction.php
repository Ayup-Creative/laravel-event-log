<?php

namespace AyupCreative\EventLog\Support;

use Closure;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Class WithEventTransaction
 *
 * A wrapper to group multiple events within a single database transaction.
 * Ensures all events logged inside the closure share the same transaction ID.
 */
class WithEventTransaction
{
    /**
     * Executes the given callback within a database transaction context.
     *
     * Initiates a transaction using the database connection and ensures proper
     * transaction handling by beginning and clearing the event transaction
     * context. The callback is executed within this transaction.
     *
     * Usage example:
     *      WithEventTransaction::run(function () use ($user, $org) {
     *          $org->save();
     *          $user->organisations()->attach($org);
     *
     *          event_log('organisation.created', $org);
     *          event_log('user.enrolled', $user, [$org]);
     *      });
     *
     * @param Closure $callback The callback function to execute within the transaction.
     * @return mixed The result of the callback execution.
     * @throws Throwable
     */
    public static function run(Closure $callback): mixed
    {
        return DB::transaction(function () use ($callback) {
            EventContext::beginTransaction();

            try {
                return $callback();
            } finally {
                EventContext::clearTransaction();
            }
        });
    }
}
