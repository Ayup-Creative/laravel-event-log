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

class WriteEventLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $event,
        public string $subjectType,
        public int|string $subjectId,
        public string $correlationId,
        public array $related = [],
        public ?int $causerId = null,
        public ?string $causerType = null,
        public ?string $transactionId = null
    ) {
        $this->queue = config('event-log.queue', 'event-log');
    }

    public function handle(): void
    {
        /** @var Model|null $subject */
        $subject = $this->subjectType::find($this->subjectId);

        // Subject deleted before job ran → do nothing
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

            $this->recordOpenTelemetryEvent();
        } catch (QueryException $e) {
            // Duplicate → event already written
            return;
        }
    }

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
