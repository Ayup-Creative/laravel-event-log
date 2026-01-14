<?php

namespace AyupCreative\EventLog\Tests\Integration;

use AyupCreative\EventLog\Observers\EventLogObserver;
use AyupCreative\EventLog\Tests\Models\DummyBook;
use AyupCreative\EventLog\Tests\Models\DummyUser;
use AyupCreative\EventLog\Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use AyupCreative\EventLog\Jobs\WriteEventLogJob;
use Illuminate\Database\Eloquent\Model;

class EventLogObserverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_it_can_be_instantiated(): void
    {
        $observer = new EventLogObserver();
        $this->assertInstanceOf(EventLogObserver::class, $observer);
    }

    public function test_it_logs_created_event_via_lifecycle(): void
    {
        Queue::fake();

        $user = DummyUser::create(['name' => 'Created Via Lifecycle']);

        Queue::assertPushed(WriteEventLogJob::class, function ($job) {
            return $job->event === 'dummy_user.created';
        });
    }

    public function test_it_logs_updated_event_with_changes(): void
    {
        $user = DummyUser::create(['name' => 'Original Name']);
        Queue::fake();

        $user->name = 'New Name';
        $user->save();

        Queue::assertPushed(WriteEventLogJob::class, function ($job) {
            return $job->event === 'dummy_user.updated';
        });
    }

    public function test_it_does_not_log_updated_event_without_changes(): void
    {
        $user = DummyUser::create(['name' => 'Same Name']);
        Queue::fake();

        $observer = new EventLogObserver();
        $observer->updated($user); // Manual call with no changes

        Queue::assertNotPushed(WriteEventLogJob::class);
    }

    public function test_it_logs_deleted_event_via_lifecycle(): void
    {
        $user = DummyUser::create(['name' => 'To Be Deleted']);
        Queue::fake();

        $user->delete();

        Queue::assertPushed(WriteEventLogJob::class, function ($job) {
            return $job->event === 'dummy_user.deleted';
        });
    }

    public function test_it_logs_restored_event_via_lifecycle(): void
    {
        $book = DummyBook::create(['title' => 'Soft Delete Book']);
        $book->delete();
        Queue::fake();

        $book->restore();

        Queue::assertPushed(WriteEventLogJob::class, function ($job) {
            return $job->event === 'dummy_book.restored';
        });
    }

    public function test_it_logs_force_deleted_event(): void
    {
        $book = DummyBook::create(['title' => 'Force Delete Book']);
        Queue::fake();

        $book->forceDelete();

        Queue::assertPushed(WriteEventLogJob::class, function ($job) {
            return $job->event === 'dummy_book.deleted';
        });
    }

    public function test_it_respects_should_log_event(): void
    {
        $model = new class extends DummyUser {
            public function shouldLogEvent(string $event): bool {
                return false;
            }
        };
        $model->id = 1;

        $observer = new EventLogObserver();
        $observer->created($model);

        Queue::assertNotPushed(WriteEventLogJob::class);
    }

    public function test_it_ignores_models_not_using_trait(): void
    {
        $model = new class extends Model {
            protected $table = 'users';
        };
        $model->id = 1;

        $observer = new EventLogObserver();
        $observer->created($model);

        Queue::assertNotPushed(WriteEventLogJob::class);
    }

    public function test_explicit_observer_calls(): void
    {
        $user = DummyUser::create(['name' => 'Test']);
        $user->name = 'Changed';
        $user->syncChanges(); // Make it look like it was saved with changes
        
        Queue::fake();
        $observer = new EventLogObserver();

        $observer->created($user);
        $observer->updated($user);
        $observer->deleted($user);
        $observer->restored($user);
        
        Queue::assertPushed(WriteEventLogJob::class, 4);
    }
}
