<?php

namespace AyupCreative\EventLog\Support;

class Idempotency
{
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
