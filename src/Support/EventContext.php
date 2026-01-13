<?php

namespace AyupCreative\EventLog\Support;

use Illuminate\Support\Str;

class EventContext
{
    protected static ?string $correlationId = null;
    protected static ?string $transactionId = null;

    public static function correlationId(): string
    {
        return static::$correlationId
            ??= (string) Str::uuid();
    }

    public static function setCorrelationId(string $id): void
    {
        static::$correlationId = $id;
    }

    public static function transactionId(): ?string
    {
        return static::$transactionId;
    }

    public static function beginTransaction(): void
    {
        static::$transactionId = (string) Str::uuid();
    }

    public static function clearTransaction(): void
    {
        static::$transactionId = null;
    }
}
