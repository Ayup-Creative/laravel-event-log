<?php

namespace AyupCreative\EventLog\Observers;

use Illuminate\Database\Eloquent\Model;

class UuidObserver
{

    /**
     * Handle the Model "creating" event.
     *
     * @param  Model  $model
     * @return void
     */
    public function creating(Model $model): void
    {
        $model->{$model->getKeyName()} = (string) \Ramsey\Uuid\Uuid::uuid4();
    }
}
