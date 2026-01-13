<?php

namespace AyupCreative\EventLog\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

interface EventRelationModel
{
    public function event(): BelongsTo;

    public function related(): MorphTo;
}
