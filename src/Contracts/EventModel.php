<?php

namespace AyupCreative\EventLog\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Interface EventModel
 *
 * Defines the contract for the main event log model.
 * Each event log record represents a fact that occurred in the system.
 */
interface EventModel
{
    /**
     * The primary model this event is about.
     *
     * @return MorphTo
     */
    public function subject(): MorphTo;

    /**
     * The user who caused the event, if applicable.
     *
     * @return BelongsTo
     */
    public function causer(): BelongsTo;

    /**
     * The collection of relational links for this event.
     *
     * @return HasMany
     */
    public function relations(): HasMany;

    /**
     * A helper to access related models directly (if they are of the configured user type).
     *
     * @return MorphToMany
     */
    public function related(): MorphToMany;

    /**
     * Get a human-readable label for the causer.
     *
     * @return string
     */
    public function causerLabel(): string;
}
