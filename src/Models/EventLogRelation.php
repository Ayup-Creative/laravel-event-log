<?php

namespace AyupCreative\EventLog\Models;

use AyupCreative\EventLog\Contracts\EventRelationModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class EventLogRelation
 *
 * Represents a link between an EventLog and a related model.
 */
class EventLogRelation extends Model implements EventRelationModel
{
    /** @var bool Indicates if the model should be timestamped. */
    public $timestamps = false;

    /** @var array<string> The attributes that aren't mass assignable. */
    protected $guarded = [];

    /** @var array<string> The attributes that are mass assignable. */
    protected $fillable = [
        'event_log_id',
        'related_type',
        'related_id',
    ];

    /**
     * Get the event log record this relation belongs to.
     *
     * @return BelongsTo
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(EventLog::class, 'event_log_id');
    }

    /**
     * Get the related model.
     *
     * @return MorphTo
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
