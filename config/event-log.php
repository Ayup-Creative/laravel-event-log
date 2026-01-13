<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The model used to represent users in your application.
    | This is used for the 'causer' relationship.
    |
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Event Models
    |--------------------------------------------------------------------------
    |
    | The Eloquent models used for event logs and their relations.
    | You can extend these to add your own logic or relationships.
    |
    */
    'event_model' => \AyupCreative\EventLog\Models\EventLog::class,
    'relation_model' => \AyupCreative\EventLog\Models\EventLogRelation::class,

    /*
    |--------------------------------------------------------------------------
    | Logging Queue
    |--------------------------------------------------------------------------
    |
    | The name of the queue that event log jobs should be sent to.
    | It is recommended to use a lower priority queue to ensure
    | zero impact on the main application flow.
    |
    */
    'queue' => 'event-log',
];
