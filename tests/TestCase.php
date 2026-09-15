<?php

namespace TheCorps\LaravelCqrs\Tests;

use TheCorps\LaravelCqrs\CqrsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /** @return array<class-string> */
    protected function getPackageProviders($app): array
    {
        return [CqrsServiceProvider::class];
    }
}
