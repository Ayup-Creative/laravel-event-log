<?php

namespace AyupCreative\EventLog\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Interface EventRelationModel
 *
 * Defines the contract for the model that links events to related models.
 */
interface EventRelationModel
{
    /**
     * The main event log record this relation belongs to.
     *
     * @return BelongsTo
     */
    public function event(): BelongsTo;

    /**
     * The related model being linked to the event.
     *
     * @return MorphTo
     */
    public function related(): MorphTo;
}
