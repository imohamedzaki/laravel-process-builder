<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Unit\Scanning;

use Illuminate\Support\Facades\Route;
use MohamedZaki\LaravelProcessBuilder\Scanning\ControllerScanner;
use MohamedZaki\LaravelProcessBuilder\Scanning\ProjectScanner;
use MohamedZaki\LaravelProcessBuilder\Scanning\RouteScanner;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;
use Workbench\App\Http\Controllers\OrderController;

final class ProjectScannerTest extends TestCase
{
    public function test_it_summarizes_routes_and_controllers(): void
    {
        Route::get('/orders-summary-test', [OrderController::class, 'index'])->name('orders-summary-test.index');
        Route::post('/orders-summary-test', [OrderController::class, 'store'])->name('orders-summary-test.store');
        Route::get('/unnamed-summary-test-route', fn () => 'ok');

        $summary = $this->scanner()->summarize();

        $this->assertGreaterThanOrEqual(3, $summary->routeCount);
        $this->assertContains(OrderController::class, array_map(
            static fn ($route) => $route->controller,
            $summary->routes,
        ));
        $this->assertGreaterThanOrEqual(2, $summary->namedRouteCount);
        $this->assertGreaterThanOrEqual(1, $summary->unnamedRouteCount);
    }

    public function test_it_detects_duplicate_route_names(): void
    {
        Route::get('/duplicate-test-a', fn () => 'a')->name('duplicate-test-name');
        Route::get('/duplicate-test-b', fn () => 'b')->name('duplicate-test-name');

        $summary = $this->scanner()->summarize();

        $this->assertContains('duplicate-test-name', $summary->duplicateRouteNames);
    }

    public function test_it_detects_routes_with_missing_controllers(): void
    {
        Route::get('/broken-controller-test', 'App\Http\Controllers\MissingController@index');

        $summary = $this->scanner()->summarize();

        $uris = array_map(static fn ($route) => $route->uri, $summary->routesWithMissingControllers);

        $this->assertContains('broken-controller-test', $uris);
    }

    public function test_it_groups_routes_by_http_method(): void
    {
        Route::get('/method-group-test-get', fn () => 'g');
        Route::post('/method-group-test-post', fn () => 'p');

        $summary = $this->scanner()->summarize();

        $this->assertGreaterThanOrEqual(1, $summary->routesByMethod['GET'] ?? 0);
        $this->assertGreaterThanOrEqual(1, $summary->routesByMethod['POST'] ?? 0);
    }

    private function scanner(): ProjectScanner
    {
        return new ProjectScanner(
            routeScanner: new RouteScanner(router: $this->app['router']),
            controllerScanner: new ControllerScanner(),
        );
    }
}
