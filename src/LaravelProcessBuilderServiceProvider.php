<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MohamedZaki\LaravelProcessBuilder\Contracts\ControllerScannerContract;
use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Contracts\RouteScannerContract;
use MohamedZaki\LaravelProcessBuilder\Http\Middleware\AuthorizeProcessBuilder;
use MohamedZaki\LaravelProcessBuilder\Http\Middleware\EnsureProcessBuilderIsEnabled;
use MohamedZaki\LaravelProcessBuilder\Repositories\FileProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Scanning\ControllerScanner;
use MohamedZaki\LaravelProcessBuilder\Scanning\ProjectScanner;
use MohamedZaki\LaravelProcessBuilder\Scanning\RouteScanner;

final class LaravelProcessBuilderServiceProvider extends ServiceProvider
{
    public const VERSION = '0.1.0';

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/process-builder.php',
            'process-builder',
        );

        $this->registerScanningServices();
        $this->registerProcessRepository();
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'process-builder');

        $this->registerRoutes();
        $this->loadManagedRouteFile();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/process-builder.php' => config_path('process-builder.php'),
            ], 'process-builder-config');

            $this->publishes([
                __DIR__.'/../dist' => public_path('vendor/process-builder'),
            ], 'process-builder-assets');
        }
    }

    private function registerRoutes(): void
    {
        if (! (bool) config('process-builder.enabled', false)) {
            return;
        }

        $allowedEnvironments = (array) config('process-builder.environments', []);

        if ($allowedEnvironments !== [] && ! $this->app->environment($allowedEnvironments)) {
            return;
        }

        $basePath = trim((string) config('process-builder.path', 'process-builder'), '/');
        $apiPrefix = trim((string) config('process-builder.api_prefix', 'api'), '/');
        $middleware = (array) config('process-builder.middleware', ['web']);

        Route::group([
            'prefix' => $basePath,
            'middleware' => array_merge(
                $middleware,
                [EnsureProcessBuilderIsEnabled::class, AuthorizeProcessBuilder::class],
            ),
            'as' => 'process-builder.',
        ], function (): void {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

            Route::group([
                'prefix' => trim((string) config('process-builder.api_prefix', 'api'), '/'),
                'as' => 'api.',
            ], function (): void {
                $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
            });
        });
    }

    private function loadManagedRouteFile(): void
    {
        $path = config('process-builder.output.routes');

        if (is_string($path) && $path !== '' && file_exists($path)) {
            $this->loadRoutesFrom($path);
        }
    }

    private function registerScanningServices(): void
    {
        $this->app->singleton(RouteScannerContract::class, function ($app): RouteScanner {
            return new RouteScanner(
                router: $app['router'],
                excludeUriPrefixes: array_values(array_map(
                    'strval',
                    (array) config('process-builder.scanner.exclude_uri_prefixes', []),
                )),
                excludeNamespaces: array_values(array_map(
                    'strval',
                    (array) config('process-builder.scanner.exclude_namespaces', []),
                )),
            );
        });

        $this->app->singleton(ControllerScannerContract::class, ControllerScanner::class);

        $this->app->singleton(ProjectScanner::class, function ($app): ProjectScanner {
            return new ProjectScanner(
                routeScanner: $app->make(RouteScannerContract::class),
                controllerScanner: $app->make(ControllerScannerContract::class),
            );
        });
    }

    private function registerProcessRepository(): void
    {
        $this->app->singleton(ProcessRepository::class, function (): FileProcessRepository {
            $directory = config('process-builder.definitions.path');

            return new FileProcessRepository(
                directory: is_string($directory) && $directory !== '' ? $directory : storage_path('process-builder/definitions'),
            );
        });
    }
}
