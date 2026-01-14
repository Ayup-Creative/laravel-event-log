<?php

namespace AyupCreative\EventLog\Models;

use AyupCreative\EventLog\Contracts\EventModel;
use AyupCreative\EventLog\Observers\UuidObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

/**
 * Class EventLog
 *
 * The main model representing an immutable fact in the system.
 *
 * @property Collection $relations
 */
#[ObservedBy(UuidObserver::class)]
class EventLog extends Model implements EventModel
{
    /** @var string The primary key type. */
    protected $keyType = 'string';

    /** @var bool Defines whether the ID column is incrementing. */
    public $incrementing = false;

    /** @var array<string> The attributes that aren't mass assignable. */
    protected $guarded = [];

    /** @var array<string> The attributes that are mass assignable. */
    protected $fillable = [
        'event',
        'subject_type',
        'subject_id',
        'causer_id',
        'causer_type',
        'initiator_id',
        'correlation_id',
        'transaction_id',
        'idempotency_key',
    ];

    /**
     * Get the primary model the event is about.
     *
     * @return MorphTo
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who caused the event.
     *
     * @return BelongsTo
     */
    public function causer(): BelongsTo
    {
        return $this->belongsTo(
            config('event-log.user_model'),
        );
    }

    /**
     * Get the additional relational links for this event.
     *
     * @return HasMany
     */
    public function relations(): HasMany
    {
        return $this->hasMany(
            config('event-log.relation_model')
        );
    }

    /**
     * Access related models directly (filtered by configured user model).
     *
     * @return MorphToMany
     */
    public function related(): MorphToMany
    {
        return $this->morphedByMany(
            config('event-log.user_model'),
            'related',
            'event_log_relations'
        );
    }

    /**
     * Get a human-readable label for the causer.
     *
     * @return string
     */
    public function causerLabel(): string
    {
        return match ($this->causer_type) {
            'user' => $this->causer?->name ?? 'Unknown user',
            'system' => 'System',
            'job' => 'Automated process',
            'webhook' => 'External service',
            default => 'Unknown',
        };
    }
}
