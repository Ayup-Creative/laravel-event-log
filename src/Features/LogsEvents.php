<?php

namespace AyupCreative\EventLog\Features;

use AyupCreative\EventLog\Observers\EventLogObserver;

trait LogsEvents
{
    public static function bootLogsEvents(): void
    {
        static::observe(EventLogObserver::class);
    }

    /**
     * Override to disable logging for specific models.
     */
    public function shouldLogEvent(string $event): bool
    {
        return true;
    }

    /**
     * Namespace used in dot-notation events.
     *
     * organisation.created
     * user.updated
     */
    public function eventNamespace(): string
    {
        return strtolower(class_basename($this));
    }

    /**
     * Related models to attach to lifecycle events.
     *
     * Return array<Model>
     */
    public function eventRelations(string $event): array
    {
        return [];
    }
}
