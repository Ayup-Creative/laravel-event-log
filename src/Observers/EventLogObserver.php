<?php

namespace AyupCreative\EventLog\Observers;

use AyupCreative\EventLog\Features\LogsEvents;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EventLogObserver
 *
 * Observes Eloquent lifecycle events and dispatches them to the event log.
 */
class EventLogObserver
{
    /**
     * Handle the Model "created" event.
     *
     * @param  Model  $model
     * @return void
     */
    public function created(Model $model): void
    {
        $this->log('created', $model);
    }

    /**
     * Handle the Model "updated" event.
     *
     * @param  Model  $model
     * @return void
     */
    public function updated(Model $model): void
    {
        // Avoid logging noop updates where no attributes were actually changed.
        if (empty($model->getChanges())) {
            return;
        }

        $this->log('updated', $model);
    }

    /**
     * Handle the Model "deleted" event.
     *
     * @param  Model  $model
     * @return void
     */
    public function deleted(Model $model): void
    {
        $this->log('deleted', $model);
    }

    /**
     * Handle the Model "restored" event.
     *
     * @param  Model  $model
     * @return void
     */
    public function restored(Model $model): void
    {
        $this->log('restored', $model);
    }

    /**
     * Internal helper to log a lifecycle action.
     *
     * @param  string  $action  The lifecycle action ('created', 'updated', etc).
     * @param  Model   $model   The model being observed.
     * @return void
     */
    protected function log(string $action, Model $model): void
    {
        // Safety check to ensure the model actually uses the LogsEvents trait.
        if (!in_array(LogsEvents::class, class_uses_recursive($model))) {
            return;
        }

        $event = "{$model->eventNamespace()}.{$action}";

        // Allow the model to decide if this specific event should be logged.
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
