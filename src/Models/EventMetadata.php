<?php

namespace AyupCreative\EventLog\Models;

use AyupCreative\EventLog\Observers\UuidObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Serializable;

/**
 * Class EventMetadata
 *
 * A relational model that stores metadata about an event log.
 */
#[ObservedBy(UuidObserver::class)]
class EventMetadata extends Model
{
    /** @var string|null The table associated with the model. */
    protected $table = 'event_log_metadata';

    /** @var string The primary key type. */
    protected $keyType = 'string';

    /** @var bool Defines whether the ID column is incrementing. */
    public $incrementing = false;

    /** @var bool Indicates if the model should be timestamped. */
    public $timestamps = false;

    /** @var array<string> The attributes that aren't mass assignable. */
    protected $guarded = [];

    /** @var array<string> The attributes that are mass assignable. */
    protected $fillable = [
        'event_id',
        'key',
        'value',
    ];

    /** @var array<string, string> The attributes that should be cast. */
    protected $casts = [
        'value' => 'json',
    ];

    /**
     * Retrieve the event log that the metadata belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function eventLog()
    {
        return $this->belongsTo(EventLog::class, 'event_id');
    }
}
