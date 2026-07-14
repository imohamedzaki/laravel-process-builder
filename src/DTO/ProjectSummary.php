<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\DTO;

final class ProjectSummary
{
    /**
     * @param  list<RouteInfo>  $routes
     * @param  list<string>  $duplicateRouteNames
     * @param  list<RouteInfo>  $routesWithMissingControllers
     */
    public function __construct(
        public readonly array $routes,
        public readonly int $routeCount,
        public readonly int $controllerCount,
        public readonly int $namedRouteCount,
        public readonly int $unnamedRouteCount,
        /** @var array<string, int> */
        public readonly array $routesByMethod,
        public readonly array $duplicateRouteNames,
        public readonly array $routesWithMissingControllers,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'routes' => array_map(static fn (RouteInfo $route): array => $route->toArray(), $this->routes),
            'routeCount' => $this->routeCount,
            'controllerCount' => $this->controllerCount,
            'namedRouteCount' => $this->namedRouteCount,
            'unnamedRouteCount' => $this->unnamedRouteCount,
            'routesByMethod' => $this->routesByMethod,
            'duplicateRouteNames' => $this->duplicateRouteNames,
            'routesWithMissingControllers' => array_map(
                static fn (RouteInfo $route): array => $route->toArray(),
                $this->routesWithMissingControllers,
            ),
        ];
    }
}
