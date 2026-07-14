<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Feature;

use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;

final class DashboardAccessTest extends TestCase
{
    public function test_dashboard_is_accessible_when_enabled(): void
    {
        $response = $this->get('/process-builder');

        $response->assertOk();
        $response->assertSee('Laravel Process Builder', false);
    }

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/process-builder/api/health');

        $response->assertOk();
        $response->assertJsonPath('data.status', 'ok');
    }

    public function test_dashboard_returns_404_when_disabled(): void
    {
        config(['process-builder.enabled' => false]);

        $this->refreshApplication();
        config(['process-builder.enabled' => false]);

        $response = $this->get('/process-builder');

        $response->assertNotFound();
    }

    public function test_dashboard_returns_404_in_disallowed_environment(): void
    {
        config(['process-builder.environments' => ['production']]);

        $this->refreshApplication();
        config(['process-builder.environments' => ['production']]);

        $response = $this->get('/process-builder');

        $response->assertNotFound();
    }
}
