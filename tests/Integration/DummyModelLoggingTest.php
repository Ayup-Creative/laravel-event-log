<?php

namespace AyupCreative\EventLog\Tests\Integration;

use AyupCreative\EventLog\Tests\Models\DummyBook;
use AyupCreative\EventLog\Tests\Models\DummyUser;
use AyupCreative\EventLog\Tests\TestCase;
use AyupCreative\EventLog\Models\EventLog;
use AyupCreative\EventLog\Jobs\WriteEventLogJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Auth;

class DummyModelLoggingTest extends TestCase
{
    public function test_dummy_user_lifecycle_is_logged(): void
    {
        Queue::fake();

        $user = DummyUser::create(['name' => 'John Doe']);

        Queue::assertPushed(WriteEventLogJob::class, function ($job) {
            return $job->event === 'dummyuser.created';
        });
    }

    public function test_dummy_user_checking_out_book(): void
    {
        Queue::fake();

        $user = DummyUser::create(['name' => 'Librarian']);
        $book = DummyBook::create(['title' => 'Laravel Guide']);

        Auth::login($user);

        // Scenario: User checks out a book
        $book->update(['user_id' => $user->id]);
        
        // Log a custom domain event
        event_log('book.checked_out', $book, [$user]);

        Queue::assertPushed(WriteEventLogJob::class, function ($job) use ($book) {
            return $job->event === 'dummybook.updated' && $job->subjectId === $book->id;
        });

        Queue::assertPushed(WriteEventLogJob::class, function ($job) use ($book, $user) {
            return $job->event === 'book.checked_out' && 
                   $job->subjectId === $book->id &&
                   $job->related[0]['id'] === $user->id;
        });

        // Scenario: User checks in a book
        $book->update(['user_id' => null]);
        event_log('book.checked_in', $book, [$user]);

        Queue::assertPushed(WriteEventLogJob::class, function ($job) use ($book, $user) {
            return $job->event === 'book.checked_in' && 
                   $job->subjectId === $book->id &&
                   $job->related[0]['id'] === $user->id;
        });
    }
}
