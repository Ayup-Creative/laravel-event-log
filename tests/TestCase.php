<?php

namespace AyupCreative\EventLog\Tests;

use AyupCreative\EventLog\EventLogServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'AyupCreative\\EventLog\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            EventLogServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('event-log.user_model', \AyupCreative\EventLog\Tests\Models\DummyUser::class);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../migrations');

        \Illuminate\Support\Facades\Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        \Illuminate\Support\Facades\Schema::create('books', function ($table) {
            $table->id();
            $table->string('title');
            $table->foreignId('user_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }
}
