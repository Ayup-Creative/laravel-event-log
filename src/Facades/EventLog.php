<?php

namespace AyupCreative\EventLog\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class EventLog
 *
 * @method static void log(string $event, \Illuminate\Database\Eloquent\Model $subject, array $related = [], ?string $causerType = null, array $metadata = [])
 * @method static void resolveActorWith(callable $callback)
 * @method static void determineCauserTypeWith(callable $callback)
 * @method static mixed resolveActor()
 * @method static string resolveCauserType()
 * @method static \Illuminate\Database\Eloquent\Collection getFor(\Illuminate\Database\Eloquent\Model $model)
 * @method static \Illuminate\Contracts\Pagination\LengthAwarePaginator getForPaginated(\Illuminate\Database\Eloquent\Model $model)
 *
 * @see \AyupCreative\EventLog\EventLogger
 */
class EventLog extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'event-log';
    }
}
