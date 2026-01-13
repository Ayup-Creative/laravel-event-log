# Laravel Event Logger

[![PHP Tests](https://github.com/Ayup-Creative/event-log/actions/workflows/phpunit.yml/badge.svg)](https://github.com/ayup-creative/event-log/actions/workflows/phpunit.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/ayup-creative/event-log.svg?style=flat-square)](https://packagist.org/packages/ayup-creative/event-log)
[![Total Downloads](https://img.shields.io/packagist/dt/ayup-creative/event-log.svg?style=flat-square)](https://packagist.org/packages/ayup-creative/event-log)
[![License](https://img.shields.io/packagist/l/ayup-creative/event-log.svg?style=flat-square)](https://packagist.org/packages/ayup-creative/event-log)

A high-performance, structured, asynchronous, and relational event logging package for Laravel. Designed for auditability, traceability, and cross-service correlation without storing sensitive payload data.

## Core Design Goals

-   **Log Facts, Not State**: Records what happened (the event), not the resulting state of the model.
-   **Privacy by Design**: Avoids storing sensitive data, JSON blobs, or PII. It links to models instead of copying their data.
-   **Async-First**: All event persistence is handled via Laravel's queue system to ensure zero impact on request performance.
-   **Exactly-Once Delivery**: Built-in idempotency ensures that retried queue jobs do not create duplicate event records.
-   **Relational Graph**: Supports a primary "subject" and multiple "related" models per event, allowing for complex traceability.
-   **Audit Ready**: Captures causers (users, system, jobs), correlation IDs, and transaction IDs.
-   **OpenTelemetry Ready**: Seamlessly integrates with distributed tracing systems.

---

## Installation

You can install the package via composer:

```bash
composer require ayup-creative/event-log
```

The service provider will automatically register itself.

You should publish and run the migrations:

```bash
php artisan vendor:publish --tag="event-log-migrations"
php artisan migrate
```

You can optionally publish the config file:

```bash
php artisan vendor:publish --tag="event-log-config"
```

---

## Configuration

The published config file `config/event-log.php` allows you to customize the models used by the package:

```php
return [
    /*
     * The model used to represent users in your application.
     * This is used for the 'causer' relationship.
     */
    'user_model' => \App\Models\User::class,

    /*
     * The Eloquent models used for event logs and their relations.
     * You can extend these to add your own logic or relationships.
     */
    'event_model' => \AyupCreative\EventLog\Models\EventLog::class,
    'relation_model' => \AyupCreative\EventLog\Models\EventLogRelation::class,

    /*
     * The name of the queue that event log jobs should be sent to.
     * It is recommended to use a lower priority queue.
     */
    'queue' => 'event-log',
];
```

---

## Usage

### 1. Manual Domain Events

Use the `event_log` helper to record significant domain events asynchronously.

```php
// Basic event
event_log('organisation.created', $organisation);

// Event with related models
event_log('user.enrolled', $user, [$organisation, $course]);
```

-   **Event Name**: A human-readable dot-notation string.
-   **Subject**: The primary Eloquent model the event is about.
-   **Related**: (Optional) An array of additional Eloquent models linked to this event.

### 2. Automatic Lifecycle Logging

Add the `LogsEvents` trait to any Eloquent model to automatically log lifecycle events (`created`, `updated`, `deleted`, `restored`).

```php
use AyupCreative\EventLog\Features\LogsEvents;
use Illuminate\Database\Eloquent\Model;

class Mandate extends Model
{
    use LogsEvents;
}
```

#### Customizing Trait Behavior

You can override these methods in your model:

```php
class Mandate extends Model
{
    use LogsEvents;

    // Change the dot-notation prefix (defaults to snake_case of class name)
    public function eventNamespace(): string
    {
        return 'billing.mandate';
    }

    // Filter which events should be logged
    public function shouldLogEvent(string $event): bool
    {
        return $event !== 'mandate.updated';
    }

    // Attach related models to automatic lifecycle events
    public function eventRelations(string $event): array
    {
        return [$this->organisation];
    }
}
```

### 3. Grouping Events with Transactions

Use the `WithEventTransaction` wrapper to group multiple events occurring within a single database transaction. This assigns a shared `transaction_id` to all events logged inside the closure.

```php
use AyupCreative\EventLog\Support\WithEventTransaction;

WithEventTransaction::run(function () use ($user, $org) {
    $org->save();
    $user->organisations()->attach($org);

    event_log('organisation.created', $org);
    event_log('user.enrolled', $user, [$org]);
});
```

### 4. Correlation IDs & Middleware

Correlation IDs allow you to trace a logical action across multiple services and background jobs.

#### Global Middleware
Add the `EventCorrelationMiddleware` to your global or web/api middleware stack to automatically capture or generate a correlation ID for every request.

```php
// app/Http/Kernel.php or bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\AyupCreative\EventLog\Http\Middleware\EventCorrelationMiddleware::class);
})
```

It looks for an `X-Correlation-ID` header in the request and ensures the same ID is returned in the response headers.

#### Propagation via HTTP Client
The package adds a macro to the Laravel HTTP client to easily propagate the correlation ID to internal services:

```php
use Illuminate\Support\Facades\Http;

Http::withEventContext()->post('https://api.other-service.com/data');
```

---

## Querying Events

You can retrieve a unified timeline of events for any model using the `EventLogger` class. This returns events where the model is either the **subject** or a **related** model.

```php
use AyupCreative\EventLog\EventLogger;

$events = EventLogger::getFor($organisation);

foreach ($events as $log) {
    echo "{$log->event} caused by {$log->causerLabel()}";
}
```

### Causer Labels
The `EventLog` model provides a `causerLabel()` helper to identify who or what triggered the event:
-   `user`: Returns the user's name (if authenticated).
-   `system`: Internal system action.
-   `job`: Action triggered by a background job.
-   `webhook`: Action triggered by an external service.

---

## Advanced Features

### Idempotency
To prevent duplicate logs during queue retries, the package generates a deterministic `idempotency_key` for every event. If a job runs twice, the database uniqueness constraint will silently prevent the second record from being created.

### OpenTelemetry Bridge
If the `open-telemetry/opentelemetry` package is installed, the logger will automatically create spans for each event. The `correlation_id` is used to maintain trace context.

---

## Testing

The package includes a comprehensive test suite. To run the tests:

```bash
composer test
```

Or manually:

```bash
vendor/bin/phpunit
```

## License

The MIT License (MIT).
