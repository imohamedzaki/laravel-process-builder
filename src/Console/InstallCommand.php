<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Console;

use Illuminate\Console\Command;
use MohamedZaki\LaravelProcessBuilder\LaravelProcessBuilderServiceProvider;

final class InstallCommand extends Command
{
    protected $signature = 'process-builder:install {--force : Overwrite existing published configuration without confirmation}';

    protected $description = 'Install Laravel Process Builder: publish configuration and create required directories';

    public function handle(): int
    {
        $this->publishConfiguration();
        $this->ensureDirectory('process-builder.definitions.path', 'process definitions');
        $this->ensureDirectory('process-builder.manifests.path', 'generation manifests');
        $this->ensureDirectory('process-builder.backups.path', 'backups');
        $this->ensureAuditLogDirectory();
        $this->ensureManagedRouteFile();
        $this->ensureOutputDirectories();
        $this->warnIfProductionGenerationEnabled();
        $this->displayEnvironmentVariables();

        $this->newLine();
        $this->components->info('Laravel Process Builder is installed.');

        return self::SUCCESS;
    }

    private function publishConfiguration(): void
    {
        $destination = config_path('process-builder.php');

        if (is_file($destination) && ! $this->option('force')) {
            if (! $this->components->confirm("Configuration already exists at [{$destination}]. Overwrite it?", false)) {
                $this->components->warn('Skipped publishing configuration.');

                return;
            }
        }

        $this->callSilently('vendor:publish', [
            '--provider' => LaravelProcessBuilderServiceProvider::class,
            '--tag' => 'process-builder-config',
            '--force' => true,
        ]);

        $this->components->task('Publish configuration', fn (): bool => is_file($destination));
    }

    private function ensureDirectory(string $configKey, string $label): void
    {
        $path = config($configKey);

        if (! is_string($path) || $path === '') {
            $this->components->warn("Configuration key [{$configKey}] is not set; skipping {$label} directory.");

            return;
        }

        $this->components->task("Create {$label} directory", function () use ($path): bool {
            if (! is_dir($path)) {
                mkdir($path, 0755, true);
            }

            return is_dir($path);
        });
    }

    private function ensureAuditLogDirectory(): void
    {
        $path = config('process-builder.audit.path');

        if (! is_string($path) || $path === '') {
            return;
        }

        $directory = dirname($path);

        $this->components->task('Create audit log directory', function () use ($directory): bool {
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            return is_dir($directory);
        });
    }

    private function ensureManagedRouteFile(): void
    {
        $path = config('process-builder.output.routes');

        if (! is_string($path) || $path === '') {
            return;
        }

        if (is_file($path)) {
            return;
        }

        $this->components->task('Create managed route file', function () use ($path): bool {
            $directory = dirname($path);

            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            file_put_contents($path, <<<'PHP'
                <?php

                declare(strict_types=1);

                /**
                 * This file is managed by Laravel Process Builder.
                 * Manual changes may be overwritten.
                 */

                use Illuminate\Support\Facades\Route;

                PHP);

            return is_file($path);
        });
    }

    private function ensureOutputDirectories(): void
    {
        /** @var array<string, mixed> $outputs */
        $outputs = (array) config('process-builder.output', []);

        foreach ($outputs as $key => $path) {
            if ($key === 'routes' || ! is_string($path) || $path === '') {
                continue;
            }

            $this->components->task("Create output directory [{$key}]", function () use ($path): bool {
                if (! is_dir($path)) {
                    mkdir($path, 0755, true);
                }

                return is_dir($path);
            });
        }
    }

    private function warnIfProductionGenerationEnabled(): void
    {
        if ($this->getLaravel()->environment('production') && (bool) config('process-builder.generation.enabled', false)) {
            $this->components->error(
                'Code generation is enabled while running in the production environment. '
                .'Set PROCESS_BUILDER_GENERATION_ENABLED=false in production.',
            );
        }
    }

    private function displayEnvironmentVariables(): void
    {
        $this->newLine();
        $this->components->info('Configure these environment variables as needed:');

        $this->line('  PROCESS_BUILDER_ENABLED=true');
        $this->line('  PROCESS_BUILDER_GENERATION_ENABLED=false');
        $this->line('  PROCESS_BUILDER_PATH=process-builder');
    }
}
