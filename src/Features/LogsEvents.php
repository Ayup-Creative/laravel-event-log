<?php

namespace AyupCreative\EventLog\Features;

use AyupCreative\EventLog\Observers\EventLogObserver;

/**
 * Trait LogsEvents
 *
 * Provides automatic lifecycle event logging for Eloquent models.
 * When used, the model will automatically record created, updated, deleted,
 * and restored events.
 */
trait LogsEvents
{
    /**
     * Boot the trait and register the observer.
     *
     * @return void
     */
    public static function bootLogsEvents(): void
    {
        static::observe(EventLogObserver::class);
    }

    /**
     * Determine if a specific event should be logged for this model.
     *
     * Override this method to implement custom filtering logic.
     *
     * @param  string  $event  The dot-notation event name.
     * @return bool
     */
    public function shouldLogEvent(string $event): bool
    {
        return true;
    }

    /**
     * Get the namespace used for dot-notation event names.
     *
     * Defaults to the snake_case of the class name.
     * Example: Organisation -> organisation.created
     *
     * @return string
     */
    public function eventNamespace(): string
    {
        return strtolower(class_basename($this));
    }

    /**
     * Get the related models to attach to automatic lifecycle events.
     *
     * Override this to link other models to every lifecycle event of this model.
     *
     * @param  string  $event  The dot-notation event name.
     * @return array<\Illuminate\Database\Eloquent\Model>
     */
    public function eventRelations(string $event): array
    {
        return [];
    }
}
