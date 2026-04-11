<?php

namespace AyupCreative\EventLog;

use AyupCreative\EventLog\Jobs\WriteEventLogJob;
use AyupCreative\EventLog\Support\EventContext;
use \BackedEnum;
use Illuminate\Database\Eloquent\Model;

function log_event(
    string|BackedEnum $event,
    Model $subject,
    array $related = [],
    ?string $causerType = null,
    array $metadata = []
): void
{
    if ($event instanceof BackedEnum) {
        $event = $event->value;
    }

    WriteEventLogJob::dispatch(
        event: $event,
        subjectType: $subject::class,
        subjectId: $subject->getKey(),
        correlationId: EventContext::correlationId(),
        related: collect($related)->map(fn ($m) => [
            'type' => $m::class,
            'id' => $m->getKey(),
        ])->all(),
        causerId: app('event-log')->resolveActor(),
        causerType: $causerType ?? app('event-log')->resolveCauserType(),
        transactionId: EventContext::transactionId(),
        metadata: $metadata
    )->onQueue(config('event-log.queue'));
}
