<?php

namespace AyupCreative\EventLog\Models;

use AyupCreative\EventLog\Contracts\EventModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class EventLog extends Model implements EventModel
{
    protected $guarded = [];

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

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): BelongsTo
    {
        return $this->belongsTo(
            config('event-log.user_model'),
        );
    }

    public function relations(): HasMany
    {
        return $this->hasMany(
            config('event-log.relation_model')
        );
    }

    public function related(): MorphToMany
    {
        return $this->morphedByMany(
            Model::class,
            'related',
            'event_log_relations'
        );
    }

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
