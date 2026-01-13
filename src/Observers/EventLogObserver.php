<?php

namespace AyupCreative\EventLog\Observers;

use AyupCreative\EventLog\Features\LogsEvents;
use Illuminate\Database\Eloquent\Model;

class EventLogObserver
{
    public function created(Model $model): void
    {
        $this->log('created', $model);
    }

    public function updated(Model $model): void
    {
        // Avoid logging noop updates
        if (empty($model->getChanges())) {
            return;
        }

        $this->log('updated', $model);
    }

    public function deleted(Model $model): void
    {
        $this->log('deleted', $model);
    }

    public function restored(Model $model): void
    {
        $this->log('restored', $model);
    }

    protected function log(string $action, Model $model): void
    {
        if (!in_array(LogsEvents::class, class_uses_recursive($model))) {
            return;
        }

        $event = "{$model->eventNamespace()}.{$action}";

        if (!$model->shouldLogEvent($event)) {
            return;
        }

        event_log(
            event: $event,
            subject: $model,
            related: $model->eventRelations($event)
        );
    }
}
