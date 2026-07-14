<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Scanning;

use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use MohamedZaki\LaravelProcessBuilder\Contracts\RouteScannerContract;
use MohamedZaki\LaravelProcessBuilder\DTO\RouteInfo;

final class RouteScanner implements RouteScannerContract
{
    /**
     * @param  list<string>  $excludeUriPrefixes
     * @param  list<string>  $excludeNamespaces
     */
    public function __construct(
        private readonly Router $router,
        private readonly array $excludeUriPrefixes = [],
        private readonly array $excludeNamespaces = [],
    ) {
    }

    /**
     * @return list<RouteInfo>
     */
    public function scan(): array
    {
        $routes = [];

        /** @var RouteCollection $collection */
        $collection = $this->router->getRoutes();

        foreach ($collection as $route) {
            $info = $this->toRouteInfo($route);

            if ($this->isExcluded($info)) {
                continue;
            }

            $routes[] = $info;
        }

        return $routes;
    }

    private function toRouteInfo(Route $route): RouteInfo
    {
        $action = $route->getActionName();
        $controller = null;
        $controllerMethod = null;

        if ($route->getActionMethod() !== '' && str_contains($action, '@')) {
            [$controller, $controllerMethod] = explode('@', $action, 2);
        } elseif (is_string($action) && $action !== 'Closure' && class_exists($action)) {
            $controller = $action;
            $controllerMethod = '__invoke';
        }

        $isVendorRoute = $controller !== null && ! str_starts_with($controller, 'App\\');

        $domain = $route->domain();

        return new RouteInfo(
            methods: array_values(array_diff($route->methods(), ['HEAD'])),
            uri: $route->uri(),
            name: $route->getName(),
            domain: is_string($domain) ? $domain : null,
            action: $action === 'Closure' ? null : $action,
            controller: $controller,
            controllerMethod: $controllerMethod,
            middleware: array_values($route->gatherMiddleware()),
            parameters: array_values($route->parameterNames()),
            isVendorRoute: $isVendorRoute,
            isPackageInternal: $this->isPackageInternalController($controller),
        );
    }

    private function isPackageInternalController(?string $controller): bool
    {
        if ($controller === null) {
            return false;
        }

        foreach ($this->excludeNamespaces as $namespace) {
            if (str_starts_with($controller, $namespace)) {
                return true;
            }
        }

        return false;
    }

    private function isExcluded(RouteInfo $info): bool
    {
        if ($info->isPackageInternal) {
            return true;
        }

        foreach ($this->excludeUriPrefixes as $prefix) {
            $prefix = trim($prefix, '/');

            if ($prefix === '') {
                continue;
            }

            if ($info->uri === $prefix || str_starts_with($info->uri, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }
}
