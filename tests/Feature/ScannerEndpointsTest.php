<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Feature;

use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;
use Workbench\App\Http\Controllers\OrderController;

final class ScannerEndpointsTest extends TestCase
{
    protected function defineWebRoutes($router): void
    {
        $router->get('/orders', [OrderController::class, 'index'])->name('orders.index');
    }

    public function test_project_summary_endpoint_returns_scan_data(): void
    {
        $response = $this->getJson('/process-builder/api/project');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => ['routeCount', 'controllerCount', 'routes'],
        ]);
    }

    public function test_project_routes_endpoint_returns_route_list(): void
    {
        $response = $this->getJson('/process-builder/api/project/routes');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'orders.index']);
    }

    public function test_project_controllers_endpoint_returns_controller_details(): void
    {
        $response = $this->getJson('/process-builder/api/project/controllers');

        $response->assertOk();
        $response->assertJsonFragment(['class' => OrderController::class]);
    }
}
