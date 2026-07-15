<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Feature\Console;

use MohamedZaki\LaravelProcessBuilder\Backup\BackupService;
use MohamedZaki\LaravelProcessBuilder\Contracts\ManifestRepository;
use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Domain\Processes\ProcessDefinition;
use MohamedZaki\LaravelProcessBuilder\Repositories\FileManifestRepository;
use MohamedZaki\LaravelProcessBuilder\Repositories\FileProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;

final class ProcessBuilderCommandsTest extends TestCase
{
    private string $definitionsDirectory;

    private string $manifestsDirectory;

    private string $backupsDirectory;

    private string $controllersDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $root = sys_get_temp_dir().'/pb-console-'.bin2hex(random_bytes(6));
        $this->definitionsDirectory = $root.'/definitions';
        $this->manifestsDirectory = $root.'/manifests';
        $this->backupsDirectory = $root.'/backups';
        $this->controllersDirectory = $root.'/app/Http/Controllers/ProcessBuilder';

        $this->app->instance(ProcessRepository::class, new FileProcessRepository($this->definitionsDirectory));
        $this->app->instance(ManifestRepository::class, new FileManifestRepository($this->manifestsDirectory));
        $this->app->instance(BackupService::class, new BackupService($this->backupsDirectory));

        config([
            'process-builder.generation.enabled' => true,
            'process-builder.output.controllers' => $this->controllersDirectory,
            'process-builder.output.routes' => dirname($this->definitionsDirectory).'/routes/process-builder.php',
            'process-builder.audit.path' => $root.'/audit.log',
        ]);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory(dirname($this->definitionsDirectory));

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

    private function createProcess(): ProcessDefinition
    {
        $repository = $this->app->make(ProcessRepository::class);

        $process = ProcessDefinition::fromArray([
            'name' => 'Create Order',
            'slug' => 'create-order',
            'entryNodeId' => 'r1',
            'nodes' => [
                ['id' => 'r1', 'type' => 'route', 'position' => ['x' => 0, 'y' => 0], 'data' => ['method' => 'POST', 'uri' => '/orders', 'name' => 'orders.store']],
                ['id' => 'c1', 'type' => 'controller', 'position' => ['x' => 1, 'y' => 0], 'data' => ['class' => 'OrderController', 'method' => 'store']],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'r1', 'target' => 'c1'],
            ],
        ]);

        $repository->save($process);

        return $process;
    }

    public function test_list_command_shows_no_processes_when_empty(): void
    {
        $this->artisan('process-builder:list')
            ->expectsOutputToContain('No process definitions found.')
            ->assertExitCode(0);
    }

    public function test_list_command_shows_saved_processes(): void
    {
        $this->createProcess();

        $this->artisan('process-builder:list')
            ->assertExitCode(0);
    }

    public function test_show_command_displays_a_process(): void
    {
        $this->createProcess();

        $this->artisan('process-builder:show', ['process' => 'create-order'])
            ->assertExitCode(0);
    }

    public function test_show_command_fails_for_a_missing_process(): void
    {
        $this->artisan('process-builder:show', ['process' => 'does-not-exist'])
            ->assertExitCode(1);
    }

    public function test_validate_command_passes_for_a_valid_process(): void
    {
        $this->createProcess();

        $this->artisan('process-builder:validate', ['process' => 'create-order'])
            ->assertExitCode(0);
    }

    public function test_validate_command_fails_for_an_invalid_process(): void
    {
        $repository = $this->app->make(ProcessRepository::class);
        $repository->save(ProcessDefinition::fromArray(['name' => 'Empty', 'slug' => 'empty']));

        $this->artisan('process-builder:validate', ['process' => 'empty'])
            ->assertExitCode(1);
    }

    public function test_validate_command_validates_all_processes_when_none_given(): void
    {
        $this->createProcess();

        $this->artisan('process-builder:validate')
            ->assertExitCode(0);
    }

    public function test_preview_command_displays_generated_files_and_a_token(): void
    {
        $this->createProcess();

        $this->artisan('process-builder:preview', ['process' => 'create-order'])
            ->expectsOutputToContain('Preview token')
            ->assertExitCode(0);
    }

    public function test_preview_command_fails_for_an_invalid_process(): void
    {
        $repository = $this->app->make(ProcessRepository::class);
        $repository->save(ProcessDefinition::fromArray(['name' => 'Empty', 'slug' => 'empty']));

        $this->artisan('process-builder:preview', ['process' => 'empty'])
            ->assertExitCode(1);
    }

    public function test_generate_command_preview_flag_does_not_write_files(): void
    {
        $this->createProcess();

        $this->artisan('process-builder:generate', ['process' => 'create-order', '--preview' => true])
            ->assertExitCode(0);

        $this->assertFileDoesNotExist($this->controllersDirectory.'/OrderController.php');
    }

    public function test_generate_command_writes_files_when_confirmed(): void
    {
        $this->createProcess();

        $this->artisan('process-builder:generate', ['process' => 'create-order'])
            ->expectsConfirmation('This will write files to your application. Continue?', 'yes')
            ->assertExitCode(0);

        $this->assertFileExists($this->controllersDirectory.'/OrderController.php');
    }

    public function test_generate_command_is_cancelled_when_confirmation_declined(): void
    {
        $this->createProcess();

        $this->artisan('process-builder:generate', ['process' => 'create-order'])
            ->expectsConfirmation('This will write files to your application. Continue?', 'no')
            ->assertExitCode(1);

        $this->assertFileDoesNotExist($this->controllersDirectory.'/OrderController.php');
    }

    public function test_generate_command_no_interaction_runs_in_testing_environment(): void
    {
        $this->createProcess();

        $this->artisan('process-builder:generate', ['process' => 'create-order', '--no-interaction' => true])
            ->assertExitCode(0);

        $this->assertFileExists($this->controllersDirectory.'/OrderController.php');
    }

    public function test_backups_and_rollback_commands_round_trip(): void
    {
        $this->createProcess();

        $this->artisan('process-builder:generate', ['process' => 'create-order', '--no-interaction' => true])
            ->assertExitCode(0);

        $originalContents = file_get_contents($this->controllersDirectory.'/OrderController.php');

        // Regenerate (force) to create a backup snapshot of the original file.
        $this->artisan('process-builder:generate', ['process' => 'create-order', '--force' => true, '--no-interaction' => true])
            ->assertExitCode(0);

        $backupService = $this->app->make(BackupService::class);
        $backups = $backupService->listBackups('create-order');

        $this->assertNotEmpty($backups);

        $this->artisan('process-builder:backups', ['process' => 'create-order'])
            ->assertExitCode(0);

        $this->artisan('process-builder:rollback', ['process' => 'create-order', 'backup' => $backups[0]->id])
            ->expectsConfirmation("Roll back [create-order] to backup [{$backups[0]->id}]?", 'yes')
            ->assertExitCode(0);

        $this->assertSame($originalContents, file_get_contents($this->controllersDirectory.'/OrderController.php'));
    }

    public function test_rollback_command_fails_for_a_missing_backup(): void
    {
        $this->createProcess();

        $this->artisan('process-builder:generate', ['process' => 'create-order', '--no-interaction' => true])
            ->assertExitCode(0);

        $this->artisan('process-builder:rollback', ['process' => 'create-order', 'backup' => 'does-not-exist'])
            ->assertExitCode(1);
    }

    public function test_scan_command_runs_successfully(): void
    {
        $this->artisan('process-builder:scan')
            ->assertExitCode(0);
    }

    public function test_doctor_command_runs_successfully(): void
    {
        $this->artisan('process-builder:doctor')
            ->assertExitCode(0);
    }
}
