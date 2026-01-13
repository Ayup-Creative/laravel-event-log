<?php

namespace AyupCreative\EventLog\Models;

use AyupCreative\EventLog\Contracts\EventRelationModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EventLogRelation extends Model implements EventRelationModel
{
    public $timestamps = false;

    protected $guarded = [];

    protected $fillable = [
        'event_log_id',
        'related_type',
        'related_id',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(EventLog::class, 'event_log_id');
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
