<?php

namespace AyupCreative\EventLog;

use AyupCreative\EventLog\Contracts\EventModel;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EventLogger
 *
 * Provides a high-level API for logging events and querying the event timeline.
 */
class EventLogger
{
    /**
     * Log a domain event.
     *
     * @param  string  $event  The dot-notation event name.
     * @param  Model   $subject  The primary model.
     * @param  array   $related  Optional related models.
     * @param  string|null  $causerType  Optional causer type override.
     * @return void
     */
    public static function log(
        string $event,
        Model $subject,
        array $related = [],
        ?string $causerType = null
    ): void {
        event_log($event, $subject, $related, $causerType);
    }

    /**
     * Retrieve a unified timeline of events for a given model.
     *
     * Returns events where the model is either the subject or a related model,
     * ordered by the most recent events first.
     *
     * @param  Model  $model
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getFor(Model $model)
    {
        return app('event')::query()
            ->whereHas('relations', function ($q) use ($model) {
                $q->where('related_type', $model::class)
                    ->where('related_id', $model->getKey());
            })
            ->orWhere(function ($q) use ($model) {
                $q->where('subject_type', $model::class)
                    ->where('subject_id', $model->getKey());
            })
            ->latest()
            ->get();
    }



    /**
     * Retrieve a unified timeline of events for a given model.
     *
     * Returns events where the model is either the subject or a related model,
     * ordered by the most recent events first.
     *
     * @param  Model  $model
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getForPaginated(Model $model)
    {
        return app('event')::query()
            ->whereHas('relations', function ($q) use ($model) {
                $q->where('related_type', $model::class)
                    ->where('related_id', $model->getKey());
            })
            ->orWhere(function ($q) use ($model) {
                $q->where('subject_type', $model::class)
                    ->where('subject_id', $model->getKey());
            })
            ->latest()
            ->paginate();
    }
}
