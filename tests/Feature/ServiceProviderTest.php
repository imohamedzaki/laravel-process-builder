<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Feature;

use MohamedZaki\LaravelProcessBuilder\LaravelProcessBuilderServiceProvider;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;

final class ServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered(): void
    {
        $this->assertTrue(
            $this->app->providerIsLoaded(LaravelProcessBuilderServiceProvider::class),
        );
    }

    public function test_config_is_merged(): void
    {
        $this->assertNotNull(config('process-builder.path'));
        $this->assertSame('process-builder', config('process-builder.path'));
    }

    public function test_dashboard_route_is_registered_when_enabled(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('process-builder.dashboard'));
    }

    public function test_health_route_is_registered(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('process-builder.api.health'));
    }
}
