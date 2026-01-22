<?php

namespace AyupCreative\EventLog\Jobs;

use AyupCreative\EventLog\Support\OpenTelemetryBridge;
use AyupCreative\EventLog\Contracts\EventModel;
use AyupCreative\EventLog\Support\Idempotency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Class WriteEventLogJob
 *
 * A queued job responsible for persisting event logs to the database.
 * Handles idempotency, causer resolution, and OpenTelemetry recording.
 */
class WriteEventLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  string  $event  The dot-notation event name.
     * @param  string  $subjectType  The class name of the subject model.
     * @param  int|string  $subjectId  The primary key of the subject model.
     * @param  string  $correlationId  Tracing ID for this logical action.
     * @param  array   $related  Array of related model types and IDs.
     * @param  int|string|null  $causerId  The ID of the user who caused the event.
     * @param  string|null  $causerType  The type of causer.
     * @param  string|null  $transactionId  Group ID for events in the same transaction.
     * @param  array   $metadata  Additional metadata for the event.
     */
    public function __construct(
        public string $event,
        public string $subjectType,
        public int|string|null $subjectId,
        public string $correlationId,
        public array $related = [],
        public int|string|null $causerId = null,
        public ?string $causerType = null,
        public ?string $transactionId = null,
        public array $metadata = []
    ) {
        $this->queue = config('event-log.queue', 'event-log');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        /** @var Model|null $subject */
        $subject = $this->subjectType::find($this->subjectId);

        // If the subject was deleted before the job ran, we don't log the event.
        if (! $subject) {
            return;
        }

        $key = Idempotency::key(
            $this->correlationId,
            $this->transactionId,
            $this->event,
            $this->subjectType,
            $this->subjectId
        );

        try {
            $log = app('event')::create([
                'idempotency_key' => $key,
                'event' => $this->event,
                'subject_type' => $this->subjectType,
                'subject_id' => $this->subjectId,
                'causer_id' => $this->causerId,
                'causer_type' => $this->causerType
                    ?? ($this->causerId ? 'user' : 'system'),
                'correlation_id' => $this->correlationId,
                'transaction_id' => $this->transactionId,
            ]);

            foreach ($this->related as $relation) {
                $log->relations()->create([
                    'related_type' => $relation['type'],
                    'related_id' => $relation['id'],
                ]);
            }

            foreach ($this->metadata as $key => $value) {
                $log->metadata()->create([
                    'key' => $key,
                    'value' => $value,
                ]);
            }

            $this->recordOpenTelemetryEvent();
        } catch (QueryException $e) {
            // If we hit a duplicate key exception, the event has already been recorded.
            // We silently exit to ensure idempotency.
            return;
        }
    }

    /**
     * Record the event in OpenTelemetry if available.
     *
     * @return void
     */
    protected function recordOpenTelemetryEvent(): void
    {
        OpenTelemetryBridge::recordEvent([
            'event' => $this->event,
            'subject_type' => $this->subjectType,
            'subject_id' => $this->subjectId,
            'correlation_id' => $this->correlationId,
        ]);
    }
}
