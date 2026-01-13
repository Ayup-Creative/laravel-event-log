<?php

use AyupCreative\EventLog\Jobs\WriteEventLogJob;
use AyupCreative\EventLog\Support\EventContext;
use Illuminate\Database\Eloquent\Model;

if (! function_exists('event_log')) {
    /**
     * Dispatch an event log asynchronously.
     *
     * @param  string  $event
     * @param  Model   $subject
     * @param  array<Model>  $related
     * @param  string|null  $causerType
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
            related: collect($related)->map(fn ($m) => [
                'type' => $m::class,
                'id' => $m->getKey(),
            ])->all(),
            causerId: auth()->id(),
            causerType: $causerType,
            correlationId: EventContext::correlationId(),
            transactionId: EventContext::transactionId()
        );
    }
}
