<?php

namespace AyupCreative\EventLog\Tests\Integration;

use AyupCreative\EventLog\Jobs\WriteEventLogJob;
use AyupCreative\EventLog\Models\EventLog;
use AyupCreative\EventLog\Tests\Models\DummyUser;
use AyupCreative\EventLog\Tests\TestCase;
use Illuminate\Support\Facades\App;
use Illuminate\Database\QueryException;
use Mockery;

class WriteEventLogJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Queue::fake();
    }

    public function test_it_does_nothing_if_subject_is_missing(): void
    {
        $job = new WriteEventLogJob(
            event: 'test.event',
            subjectType: DummyUser::class,
            subjectId: 999, // Non-existent
            correlationId: 'corr-id',
        );

        $job->handle();

        $this->assertEquals(0, EventLog::count());
    }

    public function test_it_handles_duplicate_idempotency_key_gracefully(): void
    {
        $user = DummyUser::create(['name' => 'Test']);

        $key = \AyupCreative\EventLog\Support\Idempotency::key(
            'corr-id',
            null,
            'test.event',
            $user::class,
            $user->id
        );

        EventLog::create([
            'event' => 'test.event',
            'subject_type' => $user::class,
            'subject_id' => $user->id,
            'correlation_id' => 'corr-id',
            'idempotency_key' => $key,
        ]);
        $this->assertEquals(1, EventLog::count());

        $job = new WriteEventLogJob(
            event: 'test.event',
            subjectType: $user::class,
            subjectId: $user->id,
            correlationId: 'corr-id',
        );

        // We expect it NOT to throw exception but just return
        $job->handle();

        $this->assertEquals(1, EventLog::count());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_it_records_opentelemetry_event_if_tracer_exists(): void
    {
        // Mock the class existence
        Mockery::mock('alias:OpenTelemetry\API\Trace\TracerProvider');

        $user = DummyUser::create(['name' => 'Test']);
        
        $tracer = Mockery::mock('Tracer');
        $spanBuilder = Mockery::mock('SpanBuilder');
        $span = Mockery::mock('Span');

        App::instance('otel.tracer', $tracer);

        $tracer->shouldReceive('spanBuilder')
            ->with('test.event')
            ->andReturn($spanBuilder);
        
        $spanBuilder->shouldReceive('setAttribute')->andReturnSelf();
        $spanBuilder->shouldReceive('startSpan')->andReturn($span);
        $span->shouldReceive('end')->once();

        $job = new WriteEventLogJob(
            event: 'test.event',
            subjectType: $user::class,
            subjectId: $user->id,
            correlationId: 'corr-id',
        );

        $job->handle();
    }

    public function test_it_logs_with_related_models_and_system_causer(): void
    {
        // Ensure OTEL doesn't break if class exists but tracer doesn't
        if (class_exists('OpenTelemetry\API\Trace\TracerProvider')) {
            $tracer = Mockery::mock('Tracer');
            $tracer->shouldReceive('spanBuilder')->andReturn(Mockery::mock('SpanBuilder')->shouldReceive('setAttribute')->andReturnSelf()->shouldReceive('startSpan')->andReturn(Mockery::mock('Span')->shouldReceive('end')->getMock())->getMock());
            App::instance('otel.tracer', $tracer);
        }

        $user = DummyUser::create(['name' => 'Subject']);
        $related = DummyUser::create(['name' => 'Related']);

        $job = new WriteEventLogJob(
            event: 'test.event',
            subjectType: $user::class,
            subjectId: $user->id,
            correlationId: 'corr-id',
            related: [
                ['type' => $related::class, 'id' => $related->id]
            ]
        );

        $job->handle();

        $log = EventLog::latest()->first();
        $this->assertEquals('system', $log->causer_type);
        $this->assertCount(1, $log->relations);
        $this->assertEquals($related->id, $log->relations->first()->related_id);
    }

    public function test_it_supports_uuid_causer_id(): void
    {
        // Ensure OTEL doesn't break if class exists but tracer doesn't
        if (class_exists('OpenTelemetry\API\Trace\TracerProvider')) {
            $tracer = Mockery::mock('Tracer');
            $tracer->shouldReceive('spanBuilder')->andReturn(Mockery::mock('SpanBuilder')->shouldReceive('setAttribute')->andReturnSelf()->shouldReceive('startSpan')->andReturn(Mockery::mock('Span')->shouldReceive('end')->getMock())->getMock());
            App::instance('otel.tracer', $tracer);
        }

        $user = DummyUser::create(['name' => 'Subject']);
        $uuid = (string) \Illuminate\Support\Str::uuid();

        $job = new WriteEventLogJob(
            event: 'test.event',
            subjectType: $user::class,
            subjectId: $user->id,
            correlationId: 'corr-id',
            causerId: $uuid,
            causerType: 'user'
        );

        $job->handle();

        $log = EventLog::where('event', 'test.event')->first();
        $this->assertNotNull($log);
        $this->assertEquals($uuid, $log->causer_id);
    }
}
