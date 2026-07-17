<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Tests\Feature;

use MohamedZaki\LaravelProcessBuilder\Backup\BackupService;
use MohamedZaki\LaravelProcessBuilder\Contracts\ManifestRepository;
use MohamedZaki\LaravelProcessBuilder\Contracts\ProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Repositories\FileManifestRepository;
use MohamedZaki\LaravelProcessBuilder\Repositories\FileProcessRepository;
use MohamedZaki\LaravelProcessBuilder\Tests\TestCase;

final class ProcessBackupsAndRollbackEndpointTest extends TestCase
{
    private string $definitionsDirectory;

    private string $manifestsDirectory;

    private string $backupsDirectory;

    private string $controllersDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $root = sys_get_temp_dir().'/pb-rollback-endpoint-'.bin2hex(random_bytes(6));
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

    private function createAndGenerate(): void
    {
        $this->postJson('/process-builder/api/processes', [
            'name' => 'Create Order',
            'slug' => 'create-order',
            'participants' => [['id' => 'participant_web', 'name' => 'Web user', 'guard' => 'web', 'actorType' => 'human', 'order' => 0, 'color' => null]],
            'entryNodeId' => 'r1',
            'nodes' => [
                ['id' => 'r1', 'type' => 'route', 'position' => ['x' => 0, 'y' => 0], 'data' => ['method' => 'POST', 'uri' => '/orders', 'name' => 'orders.store']],
                ['id' => 'c1', 'type' => 'controller', 'position' => ['x' => 1, 'y' => 0], 'data' => ['class' => 'OrderController', 'method' => 'store']],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'r1', 'target' => 'c1'],
            ],
        ])->assertCreated();

        $preview = $this->postJson('/process-builder/api/processes/create-order/preview');
        $token = $preview->json('data.previewToken');

        $this->postJson('/process-builder/api/processes/create-order/generate', ['previewToken' => $token])->assertOk();
    }

    public function test_it_lists_backups_created_by_regenerating(): void
    {
        $this->createAndGenerate();

        // Regenerate the identical process to trigger a backup of the first generation.
        $preview = $this->postJson('/process-builder/api/processes/create-order/preview');
        $token = $preview->json('data.previewToken');
        $this->postJson('/process-builder/api/processes/create-order/generate', ['previewToken' => $token])->assertOk();

        $response = $this->getJson('/process-builder/api/processes/create-order/backups');

        $response->assertOk();
        $this->assertGreaterThan(0, $response->json('meta.count'));
    }

    public function test_it_rolls_back_to_a_previous_backup(): void
    {
        $this->createAndGenerate();

        $originalContents = file_get_contents($this->controllersDirectory.'/OrderController.php');

        // Regenerate to create a backup snapshot of the original file.
        $preview = $this->postJson('/process-builder/api/processes/create-order/preview');
        $token = $preview->json('data.previewToken');
        $this->postJson('/process-builder/api/processes/create-order/generate', ['previewToken' => $token, 'force' => true])->assertOk();

        $backups = $this->getJson('/process-builder/api/processes/create-order/backups');
        $backupId = $backups->json('data.0.id');

        $this->assertNotNull($backupId);

        $response = $this->postJson("/process-builder/api/processes/create-order/backups/{$backupId}/rollback");

        $response->assertOk();
        $this->assertSame($originalContents, file_get_contents($this->controllersDirectory.'/OrderController.php'));
    }

    public function test_rolling_back_a_missing_backup_returns_404(): void
    {
        $this->createAndGenerate();

        $response = $this->postJson('/process-builder/api/processes/create-order/backups/does-not-exist/rollback');

        $response->assertNotFound();
    }

    public function test_listing_backups_for_a_missing_process_returns_404(): void
    {
        $response = $this->getJson('/process-builder/api/processes/does-not-exist/backups');

        $response->assertNotFound();
    }
}
