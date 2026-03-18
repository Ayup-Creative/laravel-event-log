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
    /** @var callable|null Callback to resolve the current actor ID. */
    protected $actorResolver = null;

    /** @var callable|null Callback to resolve the current causer type. */
    protected $causerTypeResolver = null;

    /**
     * Specify a callback to resolve the current actor ID.
     *
     * @param  callable  $callback
     * @return void
     */
    public function resolveActorWith(callable $callback): void
    {
        $this->actorResolver = $callback;
    }

    /**
     * Specify a callback to resolve the current causer type.
     *
     * @param  callable  $callback
     * @return void
     */
    public function determineCauserTypeWith(callable $callback): void
    {
        $this->causerTypeResolver = $callback;
    }

    /**
     * Resolve the current actor ID using the registered callback or default auth helper.
     *
     * @return mixed
     */
    public function resolveActor()
    {
        if ($this->actorResolver) {
            return call_user_func($this->actorResolver, app());
        }

        return auth()->id();
    }

    /**
     * Resolve the current causer type using the registered callback or default logic.
     *
     * @return string
     */
    public function resolveCauserType()
    {
        if ($this->causerTypeResolver) {
            return call_user_func($this->causerTypeResolver, app());
        }

        if (app()->runningInConsole()) {
            return 'worker';
        }

        return 'user';
    }

    /**
     * Log a domain event.
     *
     * @param  string  $event  The dot-notation event name.
     * @param  Model   $subject  The primary model.
     * @param  array   $related  Optional related models.
     * @param  string|null  $causerType  Optional causer type override.
     * @param  array   $metadata  Additional metadata for the event.
     * @return void
     */
    public function log(
        string $event,
        Model $subject,
        array $related = [],
        ?string $causerType = null,
        array $metadata = []
    ): void {
        event_log($event, $subject, $related, $causerType, $metadata);
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
    public function getFor(Model $model)
    {
        return $this->queryFor($model)->get();
    }

    /**
     * Retrieve a unified timeline of events for a given model.
     *
     * Returns events where the model is either the subject or a related model,
     * ordered by the most recent events first.
     *
     * @param  Model  $model
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getForPaginated(Model $model)
    {
        return $this->queryFor($model)->paginate();
    }

    /**
     * Get the base query for finding events related to a model.
     *
     * @param  Model  $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function queryFor(Model $model)
    {
        return app('event')::query()
            ->where(function ($q) use ($model) {
                $q->whereHas('relations', function ($q) use ($model) {
                    $q->where('related_type', $model::class)
                        ->where('related_id', $model->getKey());
                })
                ->orWhere(function ($q) use ($model) {
                    $q->where('subject_type', $model::class)
                        ->where('subject_id', $model->getKey());
                });
            })
            ->latest();
    }

    /**
     * Proxy static calls to the singleton in the container.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        return app('event-log')->$method(...$args);
    }
}
