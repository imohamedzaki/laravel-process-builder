<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Console;

use Illuminate\Console\Command;
use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;

final class DoctorCommand extends Command
{
    protected $signature = 'process-builder:doctor';

    protected $description = 'Check the health of the Laravel Process Builder installation';

    private int $failures = 0;

    private int $warnings = 0;

    public function handle(ProcessRepository $repository): int
    {
        $this->checkPhpVersion();
        $this->checkLaravelVersion();
        $this->checkRequiredExtensions();
        $this->checkDashboardEnabled();
        $this->checkGenerationEnabled();
        $this->checkAuthorizationConfiguration();
        $this->checkWritableDirectories();
        $this->checkManagedRouteFile();
        $this->checkProcessJsonValidity($repository);
        $this->checkFrontendAssets();
        $this->checkRouteCacheState();
        $this->checkStaleLocks();

        $this->newLine();

        if ($this->failures > 0) {
            $this->components->error("Doctor found {$this->failures} failure(s) and {$this->warnings} warning(s).");

            return self::FAILURE;
        }

        if ($this->warnings > 0) {
            $this->components->warn("Doctor found {$this->warnings} warning(s), no failures.");

            return self::SUCCESS;
        }

        $this->components->info('Everything looks good.');

        return self::SUCCESS;
    }

    private function recordPass(string $message): void
    {
        $this->components->task($message, fn (): bool => true);
    }

    private function recordFailure(string $message): void
    {
        $this->failures++;
        $this->components->task($message, fn (): bool => false);
    }

    private function recordWarning(string $message): void
    {
        $this->warnings++;
        $this->components->twoColumnDetail($message, '<fg=yellow>WARNING</>');
    }

    private function checkPhpVersion(): void
    {
        version_compare(PHP_VERSION, '8.2.0', '>=')
            ? $this->recordPass('PHP version '.PHP_VERSION.' is supported')
            : $this->recordFailure('PHP version '.PHP_VERSION.' is below the minimum supported version (8.2)');
    }

    private function checkLaravelVersion(): void
    {
        $version = $this->getLaravel()->version();

        version_compare($version, '12.0.0', '>=')
            ? $this->recordPass("Laravel version {$version} is supported")
            : $this->recordFailure("Laravel version {$version} is below the minimum supported version (12.0)");
    }

    private function checkRequiredExtensions(): void
    {
        foreach (['json', 'mbstring', 'fileinfo'] as $extension) {
            extension_loaded($extension)
                ? $this->recordPass("PHP extension [{$extension}] is loaded")
                : $this->recordFailure("PHP extension [{$extension}] is missing");
        }
    }

    private function checkDashboardEnabled(): void
    {
        (bool) config('process-builder.enabled', false)
            ? $this->recordPass('Dashboard is enabled')
            : $this->recordWarning('Dashboard is disabled (process-builder.enabled = false)');
    }

    private function checkGenerationEnabled(): void
    {
        $enabled = (bool) config('process-builder.generation.enabled', false);

        if ($enabled && $this->getLaravel()->environment('production')) {
            $this->recordFailure('Code generation is enabled in the production environment');

            return;
        }

        $enabled
            ? $this->recordWarning('Code generation is enabled — ensure this is intentional for this environment')
            : $this->recordPass('Code generation is disabled');
    }

    private function checkAuthorizationConfiguration(): void
    {
        $gate = config('process-builder.authorization_gate');

        is_string($gate) && $gate !== ''
            ? $this->recordPass("Authorization gate [{$gate}] is configured")
            : $this->recordWarning('No authorization gate is configured — access relies solely on middleware');
    }

    private function checkWritableDirectories(): void
    {
        $directories = [
            'definitions' => config('process-builder.definitions.path'),
            'manifests' => config('process-builder.manifests.path'),
            'backups' => config('process-builder.backups.path'),
        ];

        foreach ($directories as $label => $path) {
            if (! is_string($path) || $path === '') {
                $this->recordWarning("Directory for [{$label}] is not configured");

                continue;
            }

            if (! is_dir($path)) {
                $this->recordWarning("Directory for [{$label}] does not exist yet: {$path} (run process-builder:install)");

                continue;
            }

            is_writable($path)
                ? $this->recordPass("Directory for [{$label}] is writable ({$path})")
                : $this->recordFailure("Directory for [{$label}] is not writable ({$path})");
        }
    }

    private function checkManagedRouteFile(): void
    {
        $path = config('process-builder.output.routes');

        if (! is_string($path) || $path === '') {
            $this->recordWarning('Managed route file path is not configured');

            return;
        }

        is_file($path)
            ? $this->recordPass("Managed route file exists ({$path})")
            : $this->recordWarning("Managed route file does not exist yet ({$path}) — it is created on first generation or by process-builder:install");
    }

    private function checkProcessJsonValidity(ProcessRepository $repository): void
    {
        try {
            $processes = $repository->all();
        } catch (\Throwable $exception) {
            $this->recordFailure('Unable to read process definitions: '.$exception->getMessage());

            return;
        }

        $this->recordPass('All process definitions are valid JSON ('.$processes->count().' found)');
    }

    private function checkFrontendAssets(): void
    {
        $manifestPath = __DIR__.'/../../dist/manifest.json';

        is_file($manifestPath)
            ? $this->recordPass('Compiled frontend assets are present')
            : $this->recordWarning('Compiled frontend assets are missing (dist/manifest.json not found) — run npm run build');
    }

    private function checkRouteCacheState(): void
    {
        $isCached = is_file($this->getLaravel()->getCachedRoutesPath());

        if ($isCached) {
            $this->recordWarning('Application routes are cached — newly generated routes will not be picked up until the cache is cleared');

            return;
        }

        $this->recordPass('Route cache is not active');
    }

    private function checkStaleLocks(): void
    {
        $lockDirectory = storage_path('process-builder/locks');

        if (! is_dir($lockDirectory)) {
            $this->recordPass('No generation locks present');

            return;
        }

        $staleThreshold = time() - 3600;
        $stale = [];

        foreach (glob($lockDirectory.'/*.lock') ?: [] as $lockFile) {
            $modifiedAt = filemtime($lockFile);

            if ($modifiedAt !== false && $modifiedAt < $staleThreshold) {
                $stale[] = basename($lockFile);
            }
        }

        if ($stale === []) {
            $this->recordPass('No stale generation locks found');

            return;
        }

        $this->recordWarning('Stale generation lock(s) found: '.implode(', ', $stale));
    }
}
