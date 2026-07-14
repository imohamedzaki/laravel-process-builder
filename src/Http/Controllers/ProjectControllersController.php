<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use MohamedZaki\LaravelProcessBuilder\Contracts\ControllerScannerContract;
use MohamedZaki\LaravelProcessBuilder\Contracts\RouteScannerContract;
use MohamedZaki\LaravelProcessBuilder\DTO\ControllerInfo;
use MohamedZaki\LaravelProcessBuilder\DTO\RouteInfo;

final class ProjectControllersController
{
    public function __construct(
        private readonly RouteScannerContract $routeScanner,
        private readonly ControllerScannerContract $controllerScanner,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $classes = array_unique(array_filter(array_map(
            static fn (RouteInfo $route): ?string => $route->controller,
            $this->routeScanner->scan(),
        )));

        sort($classes);

        $controllers = array_map(
            fn (string $class): ControllerInfo => $this->controllerScanner->inspect($class),
            $classes,
        );

        return response()->json([
            'data' => array_map(static fn (ControllerInfo $controller): array => $controller->toArray(), $controllers),
            'meta' => [
                'count' => count($controllers),
            ],
            'errors' => [],
        ]);
    }
}
