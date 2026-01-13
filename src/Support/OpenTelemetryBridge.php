<?php

namespace AyupCreative\EventLog\Support;

class OpenTelemetryBridge
{
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
