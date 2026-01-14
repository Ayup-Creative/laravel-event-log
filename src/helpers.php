<?php

use AyupCreative\EventLog\Jobs\WriteEventLogJob;
use AyupCreative\EventLog\Support\EventContext;
use Illuminate\Database\Eloquent\Model;

if (! function_exists('event_log')) {
    /**
     * Dispatch an event log asynchronously.
     *
     * This helper captures the current context (subject, related models, causer,
     * correlation ID, and transaction ID) and dispatches a background job
     * to persist the event. This ensures the request performance is not affected
     * by logging operations.
     *
     * @param  string  $event  The dot-notation event name (e.g., 'user.created').
     * @param  Model   $subject  The primary model this event is about.
     * @param  array<Model>  $related  Additional models related to this event.
     * @param  string|null  $causerType  Optional causer type override ('user', 'system', 'job', 'webhook').
     * @return void
     */
    function event_log(
        string $event,
        Model $subject,
        array $related = [],
        ?string $causerType = null
    ): void {
        WriteEventLogJob::dispatch(
            event: $event,
            subjectType: $subject::class,
            subjectId: $subject->getKey(),
            correlationId: EventContext::correlationId(),
            related: collect($related)->map(fn ($m) => [
                'type' => $m::class,
                'id' => $m->getKey(),
            ])->all(),
            causerId: auth()->id(),
            causerType: $causerType,
            transactionId: EventContext::transactionId()
        )->onQueue(config('event-log.queue', 'event-log'));
    }
}
