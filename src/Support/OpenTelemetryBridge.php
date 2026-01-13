<?php

namespace AyupCreative\EventLog\Support;

/**
 * Class OpenTelemetryBridge
 *
 * Provides an optional integration with OpenTelemetry for distributed tracing.
 */
class OpenTelemetryBridge
{
    /**
     * Record an event as an OpenTelemetry span if the library is present.
     *
     * @param  array  $data  The event data including event name, subject, and correlation ID.
     * @return void
     */
    public static function recordEvent(array $data): void
    {
        if (! class_exists(\OpenTelemetry\API\Trace\TracerProvider::class)) {
            return;
        }

        $tracer = app('otel.tracer');

        $span = $tracer->spanBuilder($data['event'])
            ->setAttribute('subject.type', $data['subject_type'])
            ->setAttribute('subject.id', $data['subject_id'])
            ->setAttribute('correlation_id', $data['correlation_id'])
            ->startSpan();

        $span->end();
    }
}
