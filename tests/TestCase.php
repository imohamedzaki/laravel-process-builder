<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests;

use MohamedZaki\LaravelProcessBuilder\LaravelProcessBuilderServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use WithWorkbench;

    protected function getPackageProviders($app): array
    {
        return [
            LaravelProcessBuilderServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('process-builder.enabled', true);
        $app['config']->set('process-builder.environments', ['testing']);
        $app['config']->set('process-builder.authorization_gate', null);
    }
}
