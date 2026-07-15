<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Feature\Console;

use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;

final class InstallCommandTest extends TestCase
{
    private string $definitionsDirectory;

    private string $manifestsDirectory;

    private string $backupsDirectory;

    private string $auditLogPath;

    private string $routesPath;

    private string $realConfigPath;

    private ?string $originalRealConfigContents;

    private string $realAuthProviderPath;

    protected function setUp(): void
    {
        parent::setUp();

        $root = sys_get_temp_dir().'/pb-install-'.bin2hex(random_bytes(6));
        $this->definitionsDirectory = $root.'/definitions';
        $this->manifestsDirectory = $root.'/manifests';
        $this->backupsDirectory = $root.'/backups';
        $this->auditLogPath = $root.'/audit.log';
        $this->routesPath = $root.'/routes/process-builder.php';

        config([
            'process-builder.definitions.path' => $this->definitionsDirectory,
            'process-builder.manifests.path' => $this->manifestsDirectory,
            'process-builder.backups.path' => $this->backupsDirectory,
            'process-builder.audit.path' => $this->auditLogPath,
            'process-builder.output.routes' => $this->routesPath,
            'process-builder.output.controllers' => $root.'/app/Http/Controllers/ProcessBuilder',
        ]);

        // InstallCommand's publishConfiguration() step calls the real vendor:publish command,
        // which always writes to the app's real config_path() — there is no way to redirect
        // it into an isolated temp directory. To avoid ever actually writing into the real
        // workbench dev app's config directory, pre-seed a placeholder there so the command
        // always takes its "already exists, ask to overwrite" branch (which every test here
        // declines), then restore whatever was there before.
        $this->realConfigPath = config_path('process-builder.php');
        $this->originalRealConfigContents = is_file($this->realConfigPath)
            ? file_get_contents($this->realConfigPath)
            : null;

        if (! is_dir(dirname($this->realConfigPath))) {
            mkdir(dirname($this->realConfigPath), 0755, true);
        }

        file_put_contents($this->realConfigPath, '<?php // placeholder seeded by InstallCommandTest; must not be overwritten');

        // ensureAuthServiceProviderStub() writes to the real app_path() the same way
        // publishConfiguration() writes to the real config_path() above — there is no way
        // to redirect it into the isolated temp directory either. Make sure the workbench
        // dev app never ends up with a stray stub file left behind by this test.
        $this->realAuthProviderPath = app_path('Providers/ProcessBuilderAuthServiceProvider.php');
        @unlink($this->realAuthProviderPath);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory(dirname($this->definitionsDirectory));

        if ($this->originalRealConfigContents === null) {
            @unlink($this->realConfigPath);
        } else {
            file_put_contents($this->realConfigPath, $this->originalRealConfigContents);
        }

        @unlink($this->realAuthProviderPath);

        parent::tearDown();
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (glob($directory.'/*') ?: [] as $entry) {
            is_dir($entry) ? $this->removeDirectory($entry) : unlink($entry);
        }

        rmdir($directory);
    }

    private function expectConfigOverwriteDeclined(): \Illuminate\Testing\PendingCommand
    {
        return $this->artisan('process-builder:install')
            ->expectsConfirmation(
                'Configuration already exists at ['.$this->realConfigPath.']. Overwrite it?',
                'no',
            );
    }

    public function test_it_creates_required_directories_and_the_managed_route_file(): void
    {
        $this->expectConfigOverwriteDeclined()->assertExitCode(0);

        $this->assertDirectoryExists($this->definitionsDirectory);
        $this->assertDirectoryExists($this->manifestsDirectory);
        $this->assertDirectoryExists($this->backupsDirectory);
        $this->assertFileExists($this->routesPath);
        $this->assertStringContainsString(
            'This file is managed by Laravel Process Builder.',
            (string) file_get_contents($this->routesPath),
        );
    }

    public function test_it_does_not_overwrite_an_existing_managed_route_file(): void
    {
        mkdir(dirname($this->routesPath), 0755, true);
        file_put_contents($this->routesPath, '<?php // custom content');

        $this->expectConfigOverwriteDeclined()->assertExitCode(0);

        $this->assertSame('<?php // custom content', file_get_contents($this->routesPath));
    }

    public function test_it_declines_overwriting_an_existing_config_file_by_default(): void
    {
        $this->expectConfigOverwriteDeclined()->assertExitCode(0);

        $this->assertSame(
            '<?php // placeholder seeded by InstallCommandTest; must not be overwritten',
            file_get_contents($this->realConfigPath),
        );
    }

    public function test_it_creates_the_authorization_provider_stub(): void
    {
        $this->expectConfigOverwriteDeclined()->assertExitCode(0);

        $this->assertFileExists($this->realAuthProviderPath);
        $this->assertStringContainsString(
            "Gate::define('manage-process-builder'",
            (string) file_get_contents($this->realAuthProviderPath),
        );
    }

    public function test_it_does_not_overwrite_an_existing_authorization_provider_stub(): void
    {
        if (! is_dir(dirname($this->realAuthProviderPath))) {
            mkdir(dirname($this->realAuthProviderPath), 0755, true);
        }

        file_put_contents($this->realAuthProviderPath, '<?php // custom content');

        $this->expectConfigOverwriteDeclined()->assertExitCode(0);

        $this->assertSame('<?php // custom content', file_get_contents($this->realAuthProviderPath));
    }
}
