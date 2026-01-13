<?php

return [
    'user_model' => \App\Models\User::class,

    'event_model' => \AyupCreative\EventLog\Models\EventLog::class,
    'relation_model' => \AyupCreative\EventLog\Models\EventLogRelation::class,

    /**
     * The name of the queue that event log jobs should be sent to.
     */
    'queue' => 'event-log',
];
