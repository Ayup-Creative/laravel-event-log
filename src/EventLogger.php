<?php

namespace AyupCreative\EventLog;

use AyupCreative\EventLog\Contracts\EventModel;
use Illuminate\Database\Eloquent\Model;

class EventLogger
{
    public static function log(
        string $event,
        Model $subject,
        array $related = [],
        ?string $causerType = null
    ): void {
        event_log($event, $subject, $related, $causerType);
    }

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
}
