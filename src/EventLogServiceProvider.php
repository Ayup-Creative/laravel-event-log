<?php

namespace AyupCreative\EventLog;

use AyupCreative\EventLog\Contracts\EventModel;
use AyupCreative\EventLog\Contracts\EventRelationModel;
use AyupCreative\EventLog\Models\EventLog;
use AyupCreative\EventLog\Support\EventContext;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

/**
 * Class EventLogServiceProvider
 *
 * Registers the package services, configurations, and macros.
 */
class EventLogServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/event-log.php', 'event-log');

        // Bind models to the container to allow for easy overrides via config.
        $this->app->bind(EventModel::class, config('event-log.event_model'));
        $this->app->bind(EventRelationModel::class, config('event-log.relation_model'));

        // Register aliases for easier access.
        $this->app->alias(EventModel::class, 'event');
        $this->app->alias(EventRelationModel::class, 'event-relation');

        $this->macros();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../migrations');

        $this->publishables();
    }

    /**
     * Register HTTP client macros for context propagation.
     *
     * @return void
     */
    protected function macros(): void
    {
        Http::macro('withEventContext', function () {
            return Http::withHeaders([
                'X-Correlation-ID' => EventContext::correlationId(),
            ]);
        });
    }

    /**
     * Publishes the package assets such as migrations and configuration files.
     *
     * @return void
     */
    protected function publishables(): void
    {
        $this->publishes([
            __DIR__ . '/../migrations' => database_path('migrations'),
        ], groups: 'event-log-migrations');

        $this->publishes([
            __DIR__ . '/../config/event-log.php' => config_path('event-log.php'),
        ], 'event-log-config');
    }
}
