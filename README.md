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

## Versions

The package follows semantic versioning. Major version changes indicate breaking changes, while minor and patch versions introduce new features and bug fixes, respectively.

| Version | Laravel |
|---------|---------|
| 1.x     | 12.x    |
| 2.x     | 13.x    |

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

// Event with additional metadata (e.g., tracking reasons, API errors)
event_log('payment.failed', $payment, metadata: [
    'error_reason' => 'Insufficient funds',
    'provider' => 'Stripe'
]);
```

-   **Event Name**: A human-readable dot-notation string.
    -   **Subject**: The primary Eloquent model the event is about.
    -   **Related**: (Optional) An array of additional Eloquent models linked to this event.
    -   **Causer Type**: (Optional) Explicitly set the type of actor ('user', 'system', 'worker', 'cron').
    -   **Metadata**: (Optional) Key-value pairs of additional context for the event.

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

    // Include automatic metadata in lifecycle logs
    public function eventMetadata(string $event): array
    {
        return ['type' => $this->type];
    }
}
```

### 3. Custom Actor & Causer Resolution

By default, the package uses `auth()->id()` to identify the current user and determines if the action was triggered by a 'user' (web request) or 'worker' (CLI).

You can customize this behavior using the `EventLog` Facade in your `AppServiceProvider`:

```php
use AyupCreative\EventLog\Facades\EventLog;

public function boot()
{
    // Resolve the current actor ID (e.g., from a custom auth system)
    EventLog::resolveActorWith(function ($app) {
        return auth('api')->id();
    });

    // Determine the type of causer based on context
    EventLog::determineCauserTypeWith(function ($app) {
        if ($app->runningInConsole()) {
            return 'cron';
        }
        return 'user';
    });
}
```

### 4. Grouping Events with Transactions

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

### 5. Correlation IDs & Middleware

Correlation IDs allow you to trace a logical action across multiple services and background jobs.

#### Global Middleware
Add the `EventCorrelationMiddleware` to your global or web/api middleware stack to automatically capture or generate a correlation ID for every request.

```php
// app/Http/Kernel.php or bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\AyupCreative\EventLog\Http\Middleware\EventCorrelationMiddleware::class);
})
```

#### Propagation via HTTP Client
The package adds a macro to the Laravel HTTP client to easily propagate the correlation ID:

```php
use Illuminate\Support\Facades\Http;

Http::withEventContext()->post('https://api.other-service.com/data');
```

### 6. Human-Readable Event Formatting

You can map internal dot-notation event names (e.g., `user.created`) to human-readable strings (e.g., `A new user was created`). This is useful for displaying a timeline of events to end-users.

#### Using the Facade (Closure-based)
Register a formatter in your `AppServiceProvider`:

```php
use AyupCreative\EventLog\Facades\EventLog;

public function boot()
{
    EventLog::formatEventsWith(function ($eventLog) {
        return match ($eventLog->event) {
            'user.created' => "User {$eventLog->subject->name} joined the platform",
            'payment.failed' => "Payment failed: {$eventLog->meta->error_reason}",
            default => $eventLog->event,
        };
    });
}
```

#### Using a Formatter Class (Cache-friendly)
For better performance and to keep your `AppServiceProvider` clean, you can use a dedicated class. This is also required if you want to use `php artisan config:cache`, as closures cannot be serialized.

1. Create your formatter class:

```php
namespace App\Support;

class MyEventFormatter
{
    public function __invoke($eventLog)
    {
        // Custom logic to return a human-readable string
        return "Action: " . $eventLog->event;
    }
}
```

2. Register it in `config/event-log.php`:

```php
'event_formatter' => \App\Support\MyEventFormatter::class,
```

#### Accessing the Formatted Description
Once a formatter is registered, you can access the human-readable string via the `description` attribute on the `EventLog` model:

```php
$eventLog = EventLog::getFor($user)->first();
echo $eventLog->description; // "User John Doe joined the platform"
```

---

## Querying Events

You can retrieve a unified timeline of events for any model using the `EventLog` Facade. This returns events where the model is either the **subject** or a **related** model.

```php
use AyupCreative\EventLog\Facades\EventLog;

// Get all events for a model
$events = EventLog::getFor($organisation);

foreach ($events as $log) {
    echo "{$log->description} caused by {$log->causerLabel()}";
    
    // Access metadata
    echo $log->meta->error_reason;
}

// Paginated version
$paginatedEvents = EventLog::getForPaginated($organisation);
```

### Causer Labels
The `EventLog` model provides a `causerLabel()` helper to identify the actor:
-   `user`: Returns the user's name (if authenticated).
-   `system`: Internal system action.
-   `job`: Action triggered by a background job.
-   `webhook`: Action triggered by an external service.

### Metadata Helper
The `meta` attribute provides a shorthand for the metadata collection, allowing you to access values directly as properties:

```php
echo $log->meta->error_reason;
```

Alternatively, you can access the full `metadata` relationship, which returns an Eloquent collection of metadata models.

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
