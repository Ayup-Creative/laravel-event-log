<?php

namespace AyupCreative\EventLog\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface EventModel
{
    public function subject(): MorphTo;

    public function causer(): BelongsTo;

    public function relations(): HasMany;

    public function related(): MorphToMany;

    public function causerLabel(): string;
}
