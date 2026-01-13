# Laravel Event Logger

A structured, asynchronous, relational event logging package for Laravel. Designed for auditability, traceability, and cross-service correlation.

## Core Design Goals

- **Log facts, not state**: Records what happened, not the resulting state.
- **No payloads**: Avoids storing sensitive data, JSON blobs, or PII.
- **Async-first**: Persistence is handled via background jobs.
- **Immutable**: Events are never changed once written.
- **Relational**: Supports multiple related models per event.
- **Idempotent**: Safe to retry without creating duplicates.

## Installation

```bash
composer require ayup-creative/event-log
php artisan migrate
```

## Usage

### 1. Manual Logging

Use the `event_log` helper to record domain events.

```php
event_log('user.enrolled', $user, [$organisation, $course]);
```

- **Event Name**: Dot-notation string (e.g., `order.placed`).
- **Subject**: The primary model the event is about.
- **Related**: (Optional) Array of additional models linked to the event.

### 2. Automatic Lifecycle Logging

Add the `LogsEvents` trait to any Eloquent model to automatically log `created`, `updated`, `deleted`, and `restored` events.

```php
use AyupCreative\EventLog\Features\LogsEvents;

class Order extends Model
{
    use LogsEvents;
}
```

#### Customizing Automatic Logs

- **Namespace**: Override `eventNamespace()` to change the dot-notation prefix (defaults to snake_case of class name).
- **Filtering**: Override `shouldLogEvent(string $event)` to return `false` for events you don't want to log.
- **Relations**: Override `eventRelations(string $event)` to link additional models to the lifecycle event.

### 3. Transactions and Correlation

#### Correlation ID
Groups related events across requests, jobs, and services. Automatically generated per request/job.
You can supply it via the `X-Correlation-ID` HTTP header.

#### Transaction ID
Groups events that occurred within the same database transaction.

```php
use AyupCreative\EventLog\Support\WithEventTransaction;

WithEventTransaction::run(function () {
    // Your domain logic here
    // All events logged inside will share a transaction_id
});
```

## Advanced

### Idempotency
Each event generates a deterministic key based on the correlation ID, transaction ID, event name, and subject. The database enforces uniqueness, making retries safe.

### Causer
The system automatically captures the authenticated user as the causer of the event. If no user is authenticated, it defaults to `null` (system-caused).

### OpenTelemetry
Correlation IDs are compatible with Trace IDs, allowing for easy integration with OpenTelemetry for distributed tracing.

---

## Technical Debt & Known Issues

During the development of the test suite, the following issues were identified and should be addressed:

1. **Migration Bug**: `2024_02_27_100001_create_event_log_relations_table.php` has a duplicate index on `event_log_id`.
2. **Class Loading**: `src/Support/Indemptency.php` is misspelled (should be `Idempotency.php`).
3. **Property Conflict**: `WriteEventLogJob` has an incompatible `$queue` property definition with the `Queueable` trait.
4. **Middleware**: `EventCorrelationMiddleware` does not yet return the `X-Correlation-ID` in the response header.
5. **Model Fillables**: `EventLog` model is missing `correlation_id`, `transaction_id`, and `idempotency_key` in its `$fillable` array.
6. **Sync Writes**: `EventLogger::log` performs synchronous writes, which violates the "Async by Default" rule. Use the `event_log` helper instead.
7. **Observer Import**: `EventLogObserver` has a broken import for the `LogsEvents` trait.
