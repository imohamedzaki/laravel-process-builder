<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use MohamedZaki\LaravelProcessBuilder\Contracts\RouteScannerContract;
use MohamedZaki\LaravelProcessBuilder\DTO\RouteInfo;

final class ProjectRoutesController
{
    public function __construct(private readonly RouteScannerContract $scanner)
    {
    }

    public function __invoke(): JsonResponse
    {
        $routes = $this->scanner->scan();

        return response()->json([
            'data' => array_map(static fn (RouteInfo $route): array => $route->toArray(), $routes),
            'meta' => [
                'count' => count($routes),
            ],
            'errors' => [],
        ]);
    }
}
