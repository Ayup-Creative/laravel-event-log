<?php

namespace AyupCreative\EventLog\Tests;

use AyupCreative\EventLog\EventLogServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class UnitTestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            EventLogServiceProvider::class,
        ];
    }
}
