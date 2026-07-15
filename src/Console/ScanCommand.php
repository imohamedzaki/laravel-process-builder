<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Console;

use Illuminate\Console\Command;
use MohamedZaki\LaravelProcessBuilder\DTO\RouteInfo;
use MohamedZaki\LaravelProcessBuilder\Scanning\ProjectScanner;

final class ScanCommand extends Command
{
    protected $signature = 'process-builder:scan';

    protected $description = 'Scan the existing Laravel application routes and controllers (read-only)';

    public function handle(ProjectScanner $scanner): int
    {
        $summary = $scanner->summarize();

        $this->components->info("Found {$summary->routeCount} route(s) across {$summary->controllerCount} controller(s).");

        $this->table(
            ['Method', 'URI', 'Name', 'Controller', 'Action'],
            array_map(
                static fn (RouteInfo $route): array => [
                    implode('|', $route->methods),
                    $route->uri,
                    $route->name ?? '-',
                    $route->controller ?? '-',
                    $route->controllerMethod ?? '-',
                ],
                $summary->routes,
            ),
        );

        if ($summary->duplicateRouteNames !== []) {
            $this->components->warn('Duplicate route names: '.implode(', ', $summary->duplicateRouteNames));
        }

        if ($summary->routesWithMissingControllers !== []) {
            $this->components->warn('Routes with missing controllers: '.count($summary->routesWithMissingControllers));
        }

        return self::SUCCESS;
    }
}
