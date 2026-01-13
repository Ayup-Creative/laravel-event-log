<?php

namespace AyupCreative\EventLog\Support;

use Illuminate\Support\Str;

/**
 * Class EventContext
 *
 * Manages the singleton context for event logging within a single request or job.
 * Handles Correlation IDs for tracing and Transaction IDs for grouping events.
 */
class EventContext
{
    /** @var string|null The current correlation ID. */
    protected static ?string $correlationId = null;

    /** @var string|null The current transaction ID, if inside a grouped transaction. */
    protected static ?string $transactionId = null;

    /**
     * Get the current correlation ID, generating a new UUID if none exists.
     *
     * @return string
     */
    public static function correlationId(): string
    {
        return static::$correlationId
            ??= (string) Str::uuid();
    }

    /**
     * Set a specific correlation ID (e.g., from an incoming request header).
     *
     * @param  string  $id
     * @return void
     */
    public static function setCorrelationId(string $id): void
    {
        static::$correlationId = $id;
    }

    /**
     * Get the current transaction ID, or null if not in a transaction context.
     *
     * @return string|null
     */
    public static function transactionId(): ?string
    {
        return static::$transactionId;
    }

    /**
     * Start a new transaction context by generating a new UUID.
     *
     * @return void
     */
    public static function beginTransaction(): void
    {
        static::$transactionId = (string) Str::uuid();
    }

    /**
     * Clear the current transaction context.
     *
     * @return void
     */
    public static function clearTransaction(): void
    {
        static::$transactionId = null;
    }
}
