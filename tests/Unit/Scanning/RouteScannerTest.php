<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Unit\Scanning;

use Illuminate\Support\Facades\Route;
use MohamedZaki\LaravelProcessBuilder\Scanning\RouteScanner;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;
use Workbench\App\Http\Controllers\InvokableController;
use Workbench\App\Http\Controllers\OrderController;

final class RouteScannerTest extends TestCase
{
    public function test_it_scans_closure_routes(): void
    {
        Route::get('/ping', fn () => 'pong')->name('ping');

        $routes = $this->scanner()->scan();

        $ping = $this->findByUri($routes, 'ping');

        $this->assertNotNull($ping);
        $this->assertSame(['GET'], $ping->methods);
        $this->assertSame('ping', $ping->name);
        $this->assertNull($ping->controller);
    }

    public function test_it_scans_controller_routes_with_method(): void
    {
        Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');

        $routes = $this->scanner()->scan();

        $route = $this->findByUri($routes, 'orders');

        $this->assertNotNull($route);
        $this->assertSame(OrderController::class, $route->controller);
        $this->assertSame('store', $route->controllerMethod);
    }

    public function test_it_scans_invokable_controllers(): void
    {
        Route::get('/invoke', InvokableController::class);

        $routes = $this->scanner()->scan();

        $route = $this->findByUri($routes, 'invoke');

        $this->assertNotNull($route);
        $this->assertSame(InvokableController::class, $route->controller);
        $this->assertSame('__invoke', $route->controllerMethod);
    }

    public function test_it_captures_route_parameters(): void
    {
        Route::get('/orders/{order}', [OrderController::class, 'show']);

        $routes = $this->scanner()->scan();

        $route = $this->findByUri($routes, 'orders/{order}');

        $this->assertNotNull($route);
        $this->assertSame(['order'], $route->parameters);
    }

    public function test_it_captures_route_group_middleware(): void
    {
        Route::middleware(['auth'])->get('/secured', fn () => 'ok');

        $routes = $this->scanner()->scan();

        $route = $this->findByUri($routes, 'secured');

        $this->assertNotNull($route);
        $this->assertContains('auth', $route->middleware);
    }

    public function test_it_excludes_configured_uri_prefixes(): void
    {
        Route::get('/telescope/requests', fn () => 'ok');

        $scanner = new RouteScanner(
            router: $this->app['router'],
            excludeUriPrefixes: ['telescope'],
        );

        $routes = $scanner->scan();

        $this->assertNull($this->findByUri($routes, 'telescope/requests'));
    }

    public function test_it_excludes_configured_namespaces(): void
    {
        Route::get('/internal', [InvokableController::class, '__invoke']);

        $scanner = new RouteScanner(
            router: $this->app['router'],
            excludeNamespaces: [InvokableController::class],
        );

        $routes = $scanner->scan();

        $this->assertNull($this->findByUri($routes, 'internal'));
    }

    private function scanner(): RouteScanner
    {
        return new RouteScanner(router: $this->app['router']);
    }

    /**
     * @param  list<\MohamedZaki\LaravelProcessBuilder\DTO\RouteInfo>  $routes
     */
    private function findByUri(array $routes, string $uri): ?\MohamedZaki\LaravelProcessBuilder\DTO\RouteInfo
    {
        foreach ($routes as $route) {
            if ($route->uri === $uri) {
                return $route;
            }
        }

        return null;
    }
}
