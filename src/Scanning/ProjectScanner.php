<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Scanning;

use MohamedZaki\LaravelProcessBuilder\Contracts\ControllerScannerContract;
use MohamedZaki\LaravelProcessBuilder\Contracts\RouteScannerContract;
use MohamedZaki\LaravelProcessBuilder\DTO\ProjectSummary;
use MohamedZaki\LaravelProcessBuilder\DTO\RouteInfo;

final class ProjectScanner
{
    public function __construct(
        private readonly RouteScannerContract $routeScanner,
        private readonly ControllerScannerContract $controllerScanner,
    ) {
    }

    public function summarize(): ProjectSummary
    {
        $routes = $this->routeScanner->scan();

        $controllers = array_unique(array_filter(array_map(
            static fn (RouteInfo $route): ?string => $route->controller,
            $routes,
        )));

        $routesByMethod = [];
        $nameCounts = [];
        $namedCount = 0;
        $missingControllers = [];

        foreach ($routes as $route) {
            foreach ($route->methods as $method) {
                $routesByMethod[$method] = ($routesByMethod[$method] ?? 0) + 1;
            }

            if ($route->name !== null) {
                $namedCount++;
                $nameCounts[$route->name] = ($nameCounts[$route->name] ?? 0) + 1;
            }

            if ($route->controller !== null && ! $this->controllerScanner->inspect($route->controller)->exists) {
                $missingControllers[] = $route;
            }
        }

        $duplicateNames = array_keys(array_filter(
            $nameCounts,
            static fn (int $count): bool => $count > 1,
        ));

        return new ProjectSummary(
            routes: $routes,
            routeCount: count($routes),
            controllerCount: count($controllers),
            namedRouteCount: $namedCount,
            unnamedRouteCount: count($routes) - $namedCount,
            routesByMethod: $routesByMethod,
            duplicateRouteNames: $duplicateNames,
            routesWithMissingControllers: $missingControllers,
        );
    }
}
