<?php

namespace AyupCreative\EventLog\Support;

/**
 * Class Idempotency
 *
 * Provides utilities for ensuring exactly-once persistence of events.
 */
class Idempotency
{
    /**
     * Generate a deterministic unique key for an event log.
     *
     * The key is derived from the correlation ID, transaction ID, event name,
     * and the subject model's identity. This ensures that even if a background
     * job is retried, the same event will not be persisted twice.
     *
     * @param  string  $correlationId
     * @param  string|null  $transactionId
     * @param  string  $event
     * @param  string  $subjectType
     * @param  string|int  $subjectId
     * @return string
     */
    public static function key(
        string $correlationId,
        ?string $transactionId,
        string $event,
        string $subjectType,
        string|int $subjectId
    ): string {
        return hash('sha256', implode('|', [
            $correlationId,
            $transactionId ?? 'no-tx',
            $event,
            $subjectType,
            $subjectId,
        ]));
    }
}
