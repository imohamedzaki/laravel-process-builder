<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MohamedZaki\LaravelProcessBuilder\Audit\AuditLogger;
use MohamedZaki\LaravelProcessBuilder\Backup\BackupService;
use MohamedZaki\LaravelProcessBuilder\Compilation\ActionCompiler;
use MohamedZaki\LaravelProcessBuilder\Compilation\ApiResourceCompiler;
use MohamedZaki\LaravelProcessBuilder\Compilation\ClassNameResolver;
use MohamedZaki\LaravelProcessBuilder\Compilation\ControllerCompiler;
use MohamedZaki\LaravelProcessBuilder\Compilation\EventCompiler;
use MohamedZaki\LaravelProcessBuilder\Compilation\FormRequestCompiler;
use MohamedZaki\LaravelProcessBuilder\Compilation\JobCompiler;
use MohamedZaki\LaravelProcessBuilder\Compilation\ProcessCompiler;
use MohamedZaki\LaravelProcessBuilder\Compilation\RouteFileCompiler;
use MohamedZaki\LaravelProcessBuilder\Compilation\StubRenderer;
use MohamedZaki\LaravelProcessBuilder\Console\BackupsCommand;
use MohamedZaki\LaravelProcessBuilder\Console\DemoCommand;
use MohamedZaki\LaravelProcessBuilder\Console\DoctorCommand;
use MohamedZaki\LaravelProcessBuilder\Console\GenerateCommand;
use MohamedZaki\LaravelProcessBuilder\Console\InstallCommand;
use MohamedZaki\LaravelProcessBuilder\Console\ListProcessesCommand;
use MohamedZaki\LaravelProcessBuilder\Console\PreviewProcessCommand;
use MohamedZaki\LaravelProcessBuilder\Console\RollbackCommand;
use MohamedZaki\LaravelProcessBuilder\Console\ScanCommand;
use MohamedZaki\LaravelProcessBuilder\Console\ShowProcessCommand;
use MohamedZaki\LaravelProcessBuilder\Console\ValidateProcessCommand;
use MohamedZaki\LaravelProcessBuilder\Contracts\ControllerScannerContract;
use MohamedZaki\LaravelProcessBuilder\Contracts\ManifestRepository;
use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Contracts\RouteScannerContract;
use MohamedZaki\LaravelProcessBuilder\Filesystem\AtomicFileWriter;
use MohamedZaki\LaravelProcessBuilder\Generation\GenerationService;
use MohamedZaki\LaravelProcessBuilder\Http\Middleware\AuthorizeProcessBuilder;
use MohamedZaki\LaravelProcessBuilder\Http\Middleware\EnsureProcessBuilderIsEnabled;
use MohamedZaki\LaravelProcessBuilder\Repositories\FileManifestRepository;
use MohamedZaki\LaravelProcessBuilder\Repositories\FileProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Scanning\ControllerScanner;
use MohamedZaki\LaravelProcessBuilder\Scanning\ProjectScanner;
use MohamedZaki\LaravelProcessBuilder\Scanning\RouteScanner;
use MohamedZaki\LaravelProcessBuilder\Security\PreviewTokenSigner;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\AllowedConnectionsRule;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\ClassNameRule;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\FormRequestRule;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\GraphStructureRule;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\LaneReferenceRule;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\RouteCollisionRule;
use MohamedZaki\LaravelProcessBuilder\Validation\Rules\RouteRule;
use MohamedZaki\LaravelProcessBuilder\Validation\ValidationPipeline;

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
        $this->registerValidationPipeline();
        $this->registerCompiler();
        $this->registerPreviewTokenSigner();
        $this->registerGenerationServices();
        $this->registerAuditLogger();
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

            $this->commands([
                InstallCommand::class,
                DemoCommand::class,
                DoctorCommand::class,
                ScanCommand::class,
                ListProcessesCommand::class,
                ShowProcessCommand::class,
                ValidateProcessCommand::class,
                PreviewProcessCommand::class,
                GenerateCommand::class,
                BackupsCommand::class,
                RollbackCommand::class,
            ]);
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

    private function registerValidationPipeline(): void
    {
        $this->app->singleton(ValidationPipeline::class, function ($app): ValidationPipeline {
            return new ValidationPipeline([
                new GraphStructureRule(),
                new AllowedConnectionsRule(),
                new RouteRule(),
                new RouteCollisionRule($app->make(ProcessRepository::class)),
                new ClassNameRule(),
                new FormRequestRule(),
                new LaneReferenceRule(),
            ]);
        });
    }

    private function registerCompiler(): void
    {
        $this->app->singleton(StubRenderer::class, function (): StubRenderer {
            return new StubRenderer(__DIR__.'/../resources/stubs');
        });

        $this->app->singleton(ClassNameResolver::class);

        $this->app->singleton(ProcessCompiler::class, function ($app): ProcessCompiler {
            $renderer = $app->make(StubRenderer::class);
            $resolver = $app->make(ClassNameResolver::class);

            return new ProcessCompiler(
                validationPipeline: $app->make(ValidationPipeline::class),
                routeFileCompiler: new RouteFileCompiler(),
                controllerCompiler: new ControllerCompiler($renderer, $resolver),
                formRequestCompiler: new FormRequestCompiler($renderer, $resolver),
                actionCompiler: new ActionCompiler($renderer, $resolver),
                eventCompiler: new EventCompiler($renderer, $resolver),
                jobCompiler: new JobCompiler($renderer, $resolver),
                apiResourceCompiler: new ApiResourceCompiler($renderer, $resolver),
                outputDirectories: [
                    'basePath' => rtrim(str_replace('\\', '/', base_path()), '/').'/',
                    'routes' => (string) config('process-builder.output.routes'),
                    'controllers' => (string) config('process-builder.output.controllers'),
                    'requests' => (string) config('process-builder.output.requests'),
                    'actions' => (string) config('process-builder.output.actions'),
                    'services' => (string) config('process-builder.output.services'),
                    'events' => (string) config('process-builder.output.events'),
                    'jobs' => (string) config('process-builder.output.jobs'),
                    'notifications' => (string) config('process-builder.output.notifications'),
                    'resources' => (string) config('process-builder.output.resources'),
                    'tests' => (string) config('process-builder.output.tests'),
                ],
            );
        });
    }

    private function registerPreviewTokenSigner(): void
    {
        $this->app->singleton(PreviewTokenSigner::class, function ($app): PreviewTokenSigner {
            $ttl = config('process-builder.generation.preview_token_ttl', 600);

            return new PreviewTokenSigner(
                appKey: (string) $app['config']->get('app.key'),
                ttlSeconds: is_int($ttl) ? $ttl : 600,
            );
        });
    }

    private function registerGenerationServices(): void
    {
        $this->app->singleton(ManifestRepository::class, function (): FileManifestRepository {
            $directory = config('process-builder.manifests.path');

            return new FileManifestRepository(
                directory: is_string($directory) && $directory !== '' ? $directory : storage_path('process-builder/manifests'),
            );
        });

        $this->app->singleton(BackupService::class, function (): BackupService {
            $directory = config('process-builder.backups.path');
            $retention = config('process-builder.backups.retention', 20);

            return new BackupService(
                backupsRootDirectory: is_string($directory) && $directory !== '' ? $directory : storage_path('process-builder/backups'),
                retention: is_int($retention) ? $retention : 20,
            );
        });

        $this->app->singleton(AtomicFileWriter::class);

        $this->app->singleton(GenerationService::class, function ($app): GenerationService {
            return new GenerationService(
                compiler: $app->make(ProcessCompiler::class),
                writer: $app->make(AtomicFileWriter::class),
                backupService: $app->make(BackupService::class),
                manifestRepository: $app->make(ManifestRepository::class),
                lockDirectory: storage_path('process-builder/locks'),
                generationEnabled: (bool) config('process-builder.generation.enabled', false),
                allowedEnvironments: array_values(array_map('strval', (array) config('process-builder.environments', []))),
                currentEnvironment: (string) $app->environment(),
                createBackups: (bool) config('process-builder.generation.create_backups', true),
                validateSyntax: (bool) config('process-builder.generation.validate_php_syntax', true),
            );
        });
    }

    private function registerAuditLogger(): void
    {
        $this->app->singleton(AuditLogger::class, function ($app): AuditLogger {
            $path = config('process-builder.audit.path');

            return new AuditLogger(
                logPath: is_string($path) && $path !== '' ? $path : storage_path('app/process-builder/audit.log'),
                auth: $app->bound(AuthFactory::class) ? $app->make(AuthFactory::class) : null,
            );
        });
    }
}
